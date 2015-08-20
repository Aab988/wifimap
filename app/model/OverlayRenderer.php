<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:13
 */
namespace App\Model;
use Nette;

class OverlayRenderer extends Nette\Object {

	const IMAGE_HEIGHT = 256;
	const IMAGE_WIDTH = 256;

	const IMAGE_BIGGER = 320;

	private $colors = array();
	// vygeneruju 320*320 = 1,25 * 256 -> pak ořežu na 256*256



	private function createBigImage() {
		$img = imagecreate(self::IMAGE_BIGGER, self::IMAGE_BIGGER);
		$this->colors["background"] = imagecolorallocate($img, 255,0,0);
		imagecolortransparent($img,$this->colors["background"]);

		$this->colors["text"] = imagecolorallocate($img,0,0,0);
		$this->colors["wifileaks"] = imagecolorallocate($img,255,0,0);
		$this->colors["wigle"] = imagecolorallocate($img,0,255,0);

		return $img;
	}

	private function cropImage($img) {
		$newImg = imagecreate(self::IMAGE_WIDTH,self::IMAGE_HEIGHT);

		$imc = imagecolorallocate($newImg, 255, 0, 0);
		imagecolortransparent( $newImg, $imc );

		imagecopy($newImg, $img, 0,0,32,32,256,256);


		// return imagecrop($img,array("x"=>32,"y"=>32,"width"=>256,"height"=>256));
		return $newImg;

	}


	public function drawModeAll($lat1,$lat2,$lon1,$lon2,$zoom,$nets) {

		$my_img = $this->createBigImage();

		$one_pixel_lat = abs($lat2 - $lat1) / self::IMAGE_BIGGER;
		$one_pixel_lon = abs($lon2 - $lon1) /  self::IMAGE_BIGGER;

		foreach($nets as $w) {
			$y =  self::IMAGE_BIGGER - (($w->latitude - $lat1) / (double)$one_pixel_lat);
			$x = ($w->longitude - $lon1) / (double)$one_pixel_lon;

			$x = round($x);
			$y = round($y);

			if($x < 0) { $x = -$x;}
			if($y< 0) {$y = -$y;}

			if($x < self::IMAGE_BIGGER && $y <  self::IMAGE_BIGGER && imagecolorat($my_img, $x,$y) == $this->colors["wifileaks"]) {
				$x++; $y++;
			}
			if($w->id_source== 2) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $this->colors["wigle"]);
			}
			else {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $this->colors["wifileaks"]);
			}
			if($zoom > 18) {
				imagestring($my_img, 1, $x+7, $y, $w->ssid, $this->colors["text"]);
			}
		}

		return $this->cropImage($my_img);
	}

	public function drawModeHighlight($lat1,$lat2,$lon1,$lon2,$zoom,$allNets,$highlightedNets) {
		$my_img = $this->createBigImage();
		$highlighted_colour = imagecolorallocate($my_img,0,0,255);

		$one_pixel_lat = abs($lat2 - $lat1) / self::IMAGE_BIGGER;
		$one_pixel_lon = abs($lon2 - $lon1) /  self::IMAGE_BIGGER;

		$highlightedIds = array();
		foreach($highlightedNets as $key=>$hn) {
			$highlightedIds[] = $hn->toArray()["id"];
		}

		foreach($allNets as $w) {
			$y =  self::IMAGE_BIGGER - (($w->latitude - $lat1) / (double)$one_pixel_lat);
			$x = ($w->longitude - $lon1) / (double)$one_pixel_lon;

			$x = round($x);
			$y = round($y);

			if($x < 0) { $x = -$x;}
			if($y< 0) {$y = -$y;}

			if($x < self::IMAGE_BIGGER && $y <  self::IMAGE_BIGGER && imagecolorat($my_img, $x,$y) == $this->colors["wifileaks"]) {
				$x++; $y++;
			}

			if($w->id_source== 2) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $this->colors["wigle"]);
			}
			else {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $this->colors["wifileaks"]);
			}

			if(in_array($w->id,$highlightedIds)) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $highlighted_colour);
			}
			if($zoom > 18) {
				imagestring($my_img, 1, $x+7, $y, $w->ssid, $this->colors["text"]);
			}
		}
		return $this->cropImage($my_img);
	}

}