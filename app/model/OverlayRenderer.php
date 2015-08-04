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

	// lat1,lat2,lon1,lon2,zoom,nets
	public function drawModeAll($lat1,$lat2,$lon1,$lon2,$zoom,$nets) {

		$timeold = microtime(true);

		$my_img = imagecreate( self::IMAGE_WIDTH, self::IMAGE_HEIGHT );

		$imc = imagecolorallocate($my_img, 255, 0, 0);
		$background = imagecolortransparent( $my_img, $imc );
		$text_colour = imagecolorallocate( $my_img, 0, 0, 0 );
		$line_colour = imagecolorallocate( $my_img, 255, 0, 0 );

		$wigle_colour = imagecolorallocate($my_img, 0,255,0);


		$one_pixel_lat = abs($lat2 - $lat1) / self::IMAGE_WIDTH;
		$one_pixel_lon = abs($lon2 - $lon1) /  self::IMAGE_HEIGHT;

		//$wf = $this->getAllNetsInLatLngRange($lat1,$lat2,$lon1,$lon2);

		foreach($nets as $w) {
			$y =  self::IMAGE_HEIGHT - (($w->latitude - $lat1) / (double)$one_pixel_lat);
			$x = ($w->longitude - $lon1) / (double)$one_pixel_lon;

			$x = round($x);
			$y = round($y);

			if($x < 0) { $x = -$x;}
			if($y< 0) {$y = -$y;}

			if($x < self::IMAGE_WIDTH && $y <  self::IMAGE_HEIGHT && imagecolorat($my_img, $x,$y) == $line_colour) {
				$x++; $y++;
			}
			if($w->id_source== 2) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $wigle_colour);
			}
			else {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $line_colour);
			}
			if($zoom > 18) {
				imagestring($my_img, 1, $x+7, $y, $w->ssid, $text_colour);
			}
		}
		$timenew = microtime(true);
		imagestring($my_img, 3, 20, 9, round(($timenew - $timeold)*1000,2) . ' ms', $text_colour);

		return $my_img;

	}


	public function drawModeHighlight($lat1,$lat2,$lon1,$lon2,$zoom,$allNets,$highlightedNets) {
		$timeold = microtime(true);

		$my_img = imagecreate( self::IMAGE_WIDTH, self::IMAGE_HEIGHT );

		$imc = imagecolorallocate($my_img, 255, 0, 0);
		$background = imagecolortransparent( $my_img, $imc );
		$text_colour = imagecolorallocate( $my_img, 0, 0, 0 );
		$line_colour = imagecolorallocate( $my_img, 255, 0, 0 );
		$highlighted_colour = imagecolorallocate($my_img,0,0,255);

		$wigle_colour = imagecolorallocate($my_img, 0,255,0);


		$one_pixel_lat = abs($lat2 - $lat1) / self::IMAGE_WIDTH;
		$one_pixel_lon = abs($lon2 - $lon1) /  self::IMAGE_HEIGHT;

		//$wf = $this->getAllNetsInLatLngRange($lat1,$lat2,$lon1,$lon2);

		$highlightedIds = array();
		foreach($highlightedNets as $key=>$hn) {
			$highlightedIds[] = $hn->toArray()["id"];
		}

		foreach($allNets as $w) {
			$y =  self::IMAGE_HEIGHT - (($w->latitude - $lat1) / (double)$one_pixel_lat);
			$x = ($w->longitude - $lon1) / (double)$one_pixel_lon;

			$x = round($x);
			$y = round($y);

			if($x < 0) { $x = -$x;}
			if($y< 0) {$y = -$y;}

			if($x < self::IMAGE_WIDTH && $y <  self::IMAGE_HEIGHT && imagecolorat($my_img, $x,$y) == $line_colour) {
				$x++; $y++;
			}



			if($w->id_source== 2) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $wigle_colour);
			}
			else {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $line_colour);
			}

			/*dump(array_keys($highlightedNets));
			dump($w->id);*/
			if(in_array($w->id,$highlightedIds)) {
				imagefilledrectangle($my_img, $x - 2, $y - 2, $x + 2, $y + 2, $highlighted_colour);
			}
			if($zoom > 18) {
				imagestring($my_img, 1, $x+7, $y, $w->ssid, $text_colour);
			}
		}
		$timenew = microtime(true);
		imagestring($my_img, 3, 20, 9, round(($timenew - $timeold)*1000,2) . ' ms', $text_colour);

		return $my_img;
	}

}