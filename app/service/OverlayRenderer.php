<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:13
 */
namespace App\Service;
use App\Model\Mat2;
use App\Model\Wifi;
use Nette;
use App\Model\Color;
use App\Model\Coords;

class OverlayRenderer {

	const IMG_TYPE_RECTANGLE = 'rectangle';
	const IMG_TYPE_ELLIPSE = 'ellipse';

	/** image size cropped */
	const IMAGE_HEIGHT = 256;
	const IMAGE_WIDTH = 256;

	/** image size before crop */
	const IMAGE_BIGGER = 320;

	/** show net name from zoom */
	const SHOW_LABEL_ZOOM = 18;

	/** @var array alocated colors to IMG */
	private $imgcolors = array();

	/** @var array items colors */
	private $colors = array();

	/** @var int $zoom */
	private $zoom;

	/** @var resource $img */
	private $img;

	/**
	 * @param int $zoom
	 */
	public function __construct($zoom) {
		$this->zoom = (int) $zoom;
		$this->setColors();
	}

	/** sets color shades for each element */
	private function setColors() {
		$this->colors = array(
			'background' => new Color(254,254,254),
			'text' => new Color(0,0,0),
			'highlighted' => new Color(0,0,255),
			'one_net' => new Color(4,160,212),
			GoogleDownload::ID_SOURCE => new Color(192,0,0),
			WigleDownload::ID_SOURCE => new Color(208,0,0),
			WifileaksDownload::ID_SOURCE => new Color(255,0,0)
		);
	}

	/** adds colors to image */
	private function allocateColors2Img() {
		foreach($this->colors as $key=>$color) {
			$this->imgcolors[$key] = imagecolorallocate($this->img,$color->r,$color->g,$color->b);
		}
	}

	/**
	 * create image with alocated colors
	 *
	 * @param int $width
	 * @param int $height
	 */
	private function createImage($width,$height) {
		$this->img = imagecreate($width,$height);
		$this->allocateColors2Img();
		imagecolortransparent($this->img,$this->imgcolors['background']);
	}

	/**
	 * crops bigger image to default size image
	 *
	 * @uses OverlayRenderer::IMAGE_WIDTH as default width
	 * @uses OverlayRenderer::IMAGE_HEIGHT as default height
	 */
	private function cropImage() {
		$newImg = imagecreate(self::IMAGE_WIDTH,self::IMAGE_HEIGHT);
		foreach($this->colors as $color) {
			imagecolorallocate($newImg,$color->r,$color->g,$color->b);
		}
		imagecolortransparent($newImg,$this->imgcolors['background']);
		$width = imagesx($this->img);
		$height = imagesy($this->img);
		imagecopy($newImg, $this->img, 0,0,($width - self::IMAGE_WIDTH)/	2,($height - self::IMAGE_HEIGHT)/2,$width,$height);
		$this->img = $newImg;
		return $newImg;
	}


	/**
	 * counts lat lng range shown in one pixel
	 * onepxlat -> latitude range in one pixel
	 * onepxlon -> longitude range in one pixel
	 *
	 * @param Coords $coords
	 * @uses OverlayRenderer::IMAGE_BIGGER as size of image
	 * @return object
	 */
	private function getConversionRatio($coords) {
		$one_pixel_lat = abs($coords->getLatEnd() - $coords->getLatStart()) / self::IMAGE_BIGGER;
		$one_pixel_lon = abs($coords->getLonEnd() - $coords->getLonStart()) /  self::IMAGE_BIGGER;
		return (object) array("onepxlat" => $one_pixel_lat, "onepxlon" => $one_pixel_lon);
	}

	/**
	 * convert latitude/longitude to pixels
	 *
	 * @param float $latitude latitude to convert
	 * @param float $longitude longitude to convert
	 * @param float $lat1 image begin latitude
	 * @param float $lon1 image begin longitude
	 * @param float $one_pixel_lat latitude range in one pixel
	 * @param float $one_pixel_lon longitude range in one pixel
	 * @uses OverlayRenderer::IMAGE_BIGGER as height of image
	 * @return Mat2 coords in pixels
	 */
	private function latLngToPx($latitude, $longitude,$lat1,$lon1, $one_pixel_lat,$one_pixel_lon)
	{
		$y = round(self::IMAGE_BIGGER - (($latitude - $lat1) / (double)$one_pixel_lat));
		$x = round(($longitude - $lon1) / (double)$one_pixel_lon);

		if ($x < 0) { $x = -$x; }
		if ($y < 0) { $y = -$y;	}
		return new Mat2($x,$y);
	}

	/**
	 * add one point label text
	 * @param array $w
	 * @param int $x
	 * @param int $y
	 */
	private function addPointLabel($w, $x, $y) {
		if(trim($w['ssid']) == "") {
			imagestring($this->img,1, $x+7, $y, $w['mac'], $this->imgcolors["text"]);
		}
		else {
			$text = $w['ssid'];
			if(strlen($text) > 20) { $text = substr($text,0,20)."..."; }
			imagestring($this->img,1, $x+7, $y, $text, $this->imgcolors["text"]);
		}
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @param array $wifi
	 * @param int $color
	 * @param mixed $type
	 * @param bool $withLabel
	 */
	public function drawOneNet($x, $y, $width, $height, $wifi, $color, $type, $withLabel = true) {
		switch ($type) {
			case self::IMG_TYPE_RECTANGLE:
				imagefilledrectangle($this->img,$x - $width/2, $y - $height/2, $x + $width/2, $y + $height/2, $color);
				break;
			case self::IMG_TYPE_ELLIPSE:
				imagefilledellipse($this->img,$x,$y,$width,$height,$color);
				break;
		}
		if($this->zoom > self::SHOW_LABEL_ZOOM && $withLabel) {
			$this->addPointLabel($wifi,$x,$y);
		}
	}



	/**
	 * create image for MODE_ALL overlay
	 * @param Coords $coords
	 * @param array $nets
	 * @return resource image
	 */
	public function drawModeAll($coords,$nets) {
		$this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRatio($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w['latitude'],$w['longitude'],$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNet($xy->getX(),$xy->getY(),4,4,$w,$this->imgcolors[$w['id_source']],self::IMG_TYPE_RECTANGLE);
			$w = null;
		}
		$nets = null;
		$this->cropImage();
		return $this->img;
	}


	/**
	 * @param Coords $coords
	 * @param Wifi[] $nets
	 * @return Nette\Utils\Image
	 */
	public function drawModeOne($coords,$nets) {
		$this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRatio($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w->getLatitude(),$w->getLongitude(),$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNet($xy->getX(),$xy->getY(),16,16,array('ssid'=>$w->getSsid(),'mac'=>$w->getMac()),$this->imgcolors['one_net'],self::IMG_TYPE_ELLIPSE);
		}
		$this->cropImage();
		return $this->img;
	}

	/**
	 * @param Coords $coords
	 * @param Wifi[] $nets
	 * @param Wifi   $net
	 * @return resource
	 */
	public function drawCalculated(Coords $coords,$nets,Wifi $net) {
		$this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRatio($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w->getLatitude(), $w->getLongitude(), $coords->getLatStart(), $coords->getLonStart(), $op->onepxlat, $op->onepxlon);
			$this->drawOneNet($xy->getX(),$xy->getY(),16,16,array('ssid'=>$w->getSsid(),'mac'=>$w->getMac()),$this->imgcolors['one_net'],self::IMG_TYPE_ELLIPSE);
		}
		if($net->getLatitude() < $coords->getLatEnd() && $net->getLatitude()>$coords->getLatStart()
		&& $net->getLongitude() < $coords->getLonEnd() && $net->getLongitude() > $coords->getLonStart()) {
			$xy = $this->latLngToPx($net->getLatitude(),$net->getLongitude(),$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);

			$this->drawOneNet($xy->getX(),$xy->getY(),16,16,array('ssid'=>$net->getSsid(),'mac'=>$net->getMac()),$this->imgcolors[1],self::IMG_TYPE_ELLIPSE, false);
		}
		$this->cropImage();
		return $this->img;
	}

	/**
	 * @return resource
	 */
	public function drawNone() {
		$this->createImage(self::IMAGE_WIDTH,self::IMAGE_HEIGHT);
		imagestring($this->img,4,self::IMAGE_WIDTH/2-75,self::IMAGE_HEIGHT/2,'pro zobrazeni priblizte', $this->imgcolors['text']);
		return $this->img;
	}

	/**
	 * create image for MODE_HIGHLIGHT overlay
	 * @param Coords $coords
	 * @param array $allNets
	 * @param array $highlightedNets
	 * @return resource
	 */
	public function drawModeHighlight($coords,$allNets,$highlightedNets) {
		$this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRatio($coords);

		$highlightedIds = array();
		foreach($highlightedNets as $key=>$hn) {
			$highlightedIds[] = $hn['id'];
		}

		foreach($allNets as $w) {
			$xy = $this->latLngToPx($w['latitude'],$w['longitude'],$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			if(in_array($w['id'],$highlightedIds)) {
				$this->drawOneNet($xy->getX(),$xy->getY(),4,4,$w,$this->imgcolors["highlighted"],self::IMG_TYPE_RECTANGLE);
			}
			else {
				$this->drawOneNet($xy->getX(),$xy->getY(),4,4,$w,$this->imgcolors[$w['id_source']],self::IMG_TYPE_RECTANGLE);
			}
		}
		$this->cropImage();
		return $this->img;
	}

	/**
	 * @return array
	 */
	public function getImgcolors()
	{
		return $this->imgcolors;
	}

	/**
	 * @return array
	 */
	public function getColors()
	{
		return $this->colors;
	}

	/**
	 * @return int
	 */
	public function getZoom()
	{
		return $this->zoom;
	}

	/**
	 * @param int $zoom
	 */
	public function setZoom($zoom)
	{
		$this->zoom = $zoom;
	}

	/**
	 * @return Nette\Utils\Image
	 */
	public function getImg()
	{
		return $this->img;
	}

	/**
	 * @param Nette\Utils\Image $img
	 */
	public function setImg($img)
	{
		$this->img = $img;
	}


}