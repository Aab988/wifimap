<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:13
 */
namespace App\Service;
use App\Model\Wifi;
use Nette;
use App\Model\Color;
use App\Model\Coords;

class OverlayRenderer extends BaseService {

	const IMAGE_HEIGHT = 256;
	const IMAGE_WIDTH = 256;

	const IMAGE_BIGGER = 320;

	const SHOW_LABEL_ZOOM = 18;


	private $imgcolors = array();
	private $colors = array();
	// vygeneruju 320*320 = 1,25 * 256 -> pak ořežu na 256*256

	/**
	 * sets color shades for each element
	 *
	 */
	private function setColors() {
		$this->colors["background"] = new Color(254,254,254);
		$this->colors["text"] = new Color(0,0,0);
		$this->colors[GoogleDownload::ID_SOURCE] = new Color(192,0,0);
		$this->colors[WigleDownload::ID_SOURCE] = new Color(208,0,0);
		$this->colors[WifileaksDownload::ID_SOURCE] = new Color(255,0,0);
		$this->colors["highlighted"] = new Color(0,0,255);
		$this->colors["one_net"] = new Color(4,160,212);
	}

	/**
	 * adds colors to image
	 *
	 * @param resource $img
	 */
	private function allocateColors2Img($img) {
		foreach($this->colors as $key=>$color) {
			$this->imgcolors[$key] = imagecolorallocate($img,$color->r,$color->g,$color->b);
		}
	}

	/**
	 *
	 * @param int $width
	 * @param int $height
	 * @return resource
	 */
	private function createImage($width,$height) {
		$this->setColors();
		$img = imagecreate($width,$height);
		$this->allocateColors2Img($img);
		imagecolortransparent($img,$this->imgcolors["background"]);
		return $img;
	}




	/**
	 * crops bigger image to default size image
	 * @uses OverlayRenderer::IMAGE_WIDTH as default width
	 * @uses OverlayRenderer::IMAGE_HEIGHT as default height
	 * @param resource $img
	 * @return resource
	 */
	private function cropImage($img) {
		$newImg = $this->createImage(self::IMAGE_WIDTH,self::IMAGE_HEIGHT);
		$width = imagesx($img);
		$height = imagesy($img);
		imagecopy($newImg, $img, 0,0,($width - self::IMAGE_WIDTH)/	2,($height - self::IMAGE_HEIGHT)/2,self::IMAGE_WIDTH,self::IMAGE_HEIGHT);
		// return imagecrop($img,array("x"=>32,"y"=>32,"width"=>256,"height"=>256));
		return $newImg;
	}

	/**
	 * counts lat lng range shown in one pixel
	 *
	 * onepxlat -> latitude range in one pixel
	 * onepxlon -> longitude range in one pixel
	 *
	 * @param Coords $coords
	 * @uses OverlayRenderer::IMAGE_BIGGER as size of image
	 * @return object
	 */
	private function getConversionRation($coords) {
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
	 * @return object coords in pixels
	 */
	private function latLngToPx($latitude, $longitude,$lat1,$lon1, $one_pixel_lat,$one_pixel_lon)
	{
		$y = round(self::IMAGE_BIGGER - (($latitude - $lat1) / (double)$one_pixel_lat));
		$x = round(($longitude - $lon1) / (double)$one_pixel_lon);

		if ($x < 0) {
			$x = -$x;
		}
		if ($y < 0) {
			$y = -$y;
		}
		return (object) array("x"=>$x, "y"=>$y);
	}


	/**
	 * add one point label text
	 * @param resource $img
	 * @param object $w
	 * @param int $x
	 * @param int $y
	 */
	private function addPointLabel($img, $w, $x, $y) {


		if(trim($w->ssid) == "") {
			imagestring($img, 1, $x+7, $y, $w->mac, $this->imgcolors["text"]);
		}
		else {
			$text = $w->ssid; $dots = "...";
			if(strlen($text) > 20) { $text = $text.$dots; }

			imagestring($img, 1, $x+7, $y, $text, $this->imgcolors["text"]);
		}
	}


	/**
	 * create image for MODE_ALL overlay
	 * @param Coords $coords
	 * @param int $zoom
	 * @param array $nets
	 * @return resource image
	 */
	public function drawModeAll($coords,$zoom,$nets) {

		$my_img = $this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRation($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w->latitude,$w->longitude,$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNetModeAll($my_img,$w,$xy->x,$xy->y,$zoom);
		}
		return $this->cropImage($my_img);
	}


	public function drawModeOne($coords,$zoom,$nets) {
		$my_img = $this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRation($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w->latitude,$w->longitude,$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNetModeOne($my_img,$w,$xy->x,$xy->y,$zoom);
		}
		return $this->cropImage($my_img);
	}


	private function drawOneNetModeOne($img,$w,$x,$y,$zoom) {
		imagefilledellipse($img,$x,$y,16,16,$this->imgcolors['one_net']);
		if($zoom > self::SHOW_LABEL_ZOOM) {
			$this->addPointLabel($img,$w,$x+7,$y-1);
		}
	}


	public function drawCalculated(Coords $coords,$zoom,$nets,Wifi $net) {
		$my_img = $this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRation($coords);

		foreach($nets as $w) {
			$xy = $this->latLngToPx($w->latitude, $w->longitude, $coords->getLatStart(), $coords->getLonStart(), $op->onepxlat, $op->onepxlon);
			$this->drawOneNetModeOne($my_img, $w, $xy->x, $xy->y, $zoom);
		}
		if($net->getLatitude() < $coords->getLatEnd() && $net->getLatitude()>$coords->getLatStart()
		&& $net->getLongitude() < $coords->getLonEnd() && $net->getLongitude() > $coords->getLonStart()) {
			$xy = $this->latLngToPx($net->getLatitude(),$net->getLongitude(),$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNetCalculated($my_img,$xy->x,$xy->y);
		}
		return $this->cropImage($my_img);
	}

	private function drawOneNetCalculated($img,$x,$y) {
		imagefilledellipse($img,$x,$y,16,16,$this->imgcolors[1]);
	}




	public function drawNone() {
		$my_img = $this->createImage(self::IMAGE_WIDTH,self::IMAGE_HEIGHT);
		imagestring($my_img,4,self::IMAGE_WIDTH/2-75,self::IMAGE_HEIGHT/2,'pro zobrazeni priblizte', $this->imgcolors['text']);
		return $my_img;
	}

	/**
	 * draw one net to MODE_ALL overlay
	 * @param resource $img
	 * @param object $w
	 * @param int $x
	 * @param int $y
	 * @param int $zoom
	 * @uses OverlayRenderer::SHOW_LABEL_ZOOM as zoom from which is shown text label
	 */
	private function drawOneNetModeAll($img,$w,$x,$y,$zoom) {
		imagefilledrectangle($img, $x - 2, $y - 2, $x + 2, $y + 2, $this->imgcolors[$w->id_source]);
		if($zoom > self::SHOW_LABEL_ZOOM) {
			$this->addPointLabel($img,$w,$x,$y);
		}
	}

	/**
	 * create image for MODE_HIGHLIGHT overlay
	 * @param Coords $coords
	 * @param int $zoom
	 * @param array $allNets
	 * @param array $highlightedNets
	 * @return resource
	 */
	public function drawModeHighlight($coords,$zoom,$allNets,$highlightedNets) {
		$my_img = $this->createImage(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$op = $this->getConversionRation($coords);

		$highlightedIds = array();
		foreach($highlightedNets as $key=>$hn) {
			$highlightedIds[] = $hn->toArray()["id"];
		}

		foreach($allNets as $w) {
			$xy = $this->latLngToPx($w->latitude,$w->longitude,$coords->getLatStart(),$coords->getLonStart(),$op->onepxlat,$op->onepxlon);
			$this->drawOneNetModeHighlight($my_img,$w,$xy->x,$xy->y,$zoom,$highlightedIds);
		}
		return $this->cropImage($my_img);
	}

	/**
	 * draw one net to MODE_HIGHLIGHT overlay
	 *
	 * @param resource $img
	 * @param object $w
	 * @param int $x
	 * @param int $y
	 * @param int $zoom
	 * @param int[] $highlightedIds
	 */
	private function drawOneNetModeHighlight($img,$w,$x,$y,$zoom,$highlightedIds) {
		imagefilledrectangle($img, $x - 2, $y - 2, $x + 2, $y + 2, $this->imgcolors[$w->id_source]);
		if(in_array($w->id,$highlightedIds)) {
			imagefilledrectangle($img, $x - 2, $y - 2, $x + 2, $y + 2, $this->imgcolors["highlighted"]);
		}
		if($zoom > self::SHOW_LABEL_ZOOM) {
			$this->addPointLabel($img,$w,$x,$y);
		}
	}

}