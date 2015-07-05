<?php

namespace App\Presenters;
use Nette;
use App\Model\Wifi;
//use Nette\Caching\Cache;

class WifiPresenter extends BasePresenter {
    public $database;
   // private $cache;
	
    public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
        
        // cache
     /*   $storage = new Nette\Caching\Storages\FileStorage('../temp/sql_cache');
        $this->cache = new Cache($storage);*/
    }

    public function renderProcessClick($click_lat, $click_lon, $map_lat1, $map_lat2, $map_lon1, $map_lon2) {
        $deltaLon = $map_lon2 - $map_lon1;
        if($map_lon2 < $map_lon1) {
            $deltaLon = $map_lon1 - $map_lon2;
        }

        $deltaLat = $map_lat2 - $map_lat1;
        if($map_lat2 < $map_lat1) {
            $deltaLat = $map_lat1 - $map_lat2;
        }

        $lat1 = (doubleval($click_lat)-0.03*$deltaLat);
        $lat2 = (doubleval($click_lat)+0.03*$deltaLat);

        if($lat1 > $lat2) {
            $pom = $lat2;
            $lat2 = $lat1;
            $lat1 = $pom;
        }

        $lon1 = (doubleval($click_lon)-0.03*$deltaLon);
        $lon2 = (doubleval($click_lon)+0.03*$deltaLon);

        if($lon1 > $lon2) {
            $pom = $lon1;
            $lon1 = $lon2;
            $lon2 = $pom;
        }

        $sql = "select latitude,longtitude,ssid,mac,altitude,channel, SQRT(POW(latitude-$click_lat,2)+POW(longtitude-$click_lon,2)) as distance
                from wifi
                where latitude > $lat1 and latitude < $lat2 and longtitude > $lon1 and longtitude < $lon2
                order by distance";

        $wf = $this->database->query($sql);
        $array = array();
        foreach($wf as $key=>$w) {
            $wifi = new Wifi();
            $wifi->setLatitude($w->latitude);
            $wifi->setLongtitude($w->longtitude);
            $wifi->setSsid($w->ssid);
            $wifi->setMac($w->mac);
            $wifi->setChannel($w->channel);
            $wifi->setAltitude($w->altitude);

            $array[] = $wifi;
        }
        $this->template->nets = $array;
    }
    
    public function renderJson($lat1, $lat2, $lon1, $lon2, $zoom) {
        
        // spocitam rozdil latitud a longtitud
        $dlat = doubleval($lat2) - doubleval($lat1);
        $dlon = doubleval($lon2) - doubleval($lon1);
        if($dlat < 0) $dlat = -$dlat;
        if($dlon < 0) $dlon = -$dlon;
        
        // zvetsim nacitanou plochu
        $lat1c = $lat1 - (0 * $dlat);
        $lat2c = $lat2 + (0 * $dlat);
    
        $lon1c = $lon1 - (0 * $dlon);
        $lon2c = $lon2 + (0 * $dlon);
    
        $sql = "select latitude,longtitude,ssid,mac from wifi where latitude > $lat1c and latitude < $lat2c and longtitude > $lon1c and longtitude < $lon2c";
       
        if($zoom < 19) {
            $sql .= " limit 500";
        }  
        
        // key pro cache
        /*$key = round($lat1c, 3) . round($lat2c,3) . round($lon1c,3) . round($lon2c,3) . $zoom;

        $value = $this->cache->load($key);
        if ($value === NULL) {*/
            $wf = $this->database->query($sql);
            $array = array();
            foreach($wf as $w) {
                $a = array("ssid" => $w->ssid, "latitude"=>$w->latitude, "longtitude"=>$w->longtitude, "mac"=>$w->mac);
                $array[] = $a;
            }
        
            //$this->cache->save($key, $array, array(Cache::EXPIRE => '10 minutes'));
      /*  }
        else { $array = $value; }*/
        echo json_encode($array);
        
    }
    
    
    public function renderImage($lat1, $lat2, $lon1, $lon2, $zoom) {
        $timeold = microtime(true);

        $width = 256;
        $height = 256;

        $sql = "select latitude,longtitude,ssid,mac,id_zdroj from wifi
            where latitude > ? and latitude < ?
            and longtitude > ? and longtitude < ?";

        
        $wf = $this->database->query($sql,$lat1,$lat2,$lon1,$lon2)->fetchAll();

        $my_img = imagecreate( $width, $height );
        
        $imc = imagecolorallocate($my_img, 255, 0, 0);
        $background = imagecolortransparent( $my_img, $imc );
        $text_colour = imagecolorallocate( $my_img, 0, 0, 0 );
        $line_colour = imagecolorallocate( $my_img, 255, 0, 0 );

        $wigle_colour = imagecolorallocate($my_img, 0,255,0);


        $one_pixel_lat = ($lat2 - $lat1) / $width;
        $one_pixel_lon = ($lon2 - $lon1) / $height;

        foreach($wf as $w) {
            $y = $height - (($w->latitude - $lat1) / (double)$one_pixel_lat);
            $x = ($w->longtitude - $lon1) / (double)$one_pixel_lon;

            $x = round($x);
            $y = round($y);

            if($x < 0) { $x = -$x;}
            if($y< 0) {$y = -$y;}

            if($x < $width && $y < $height && imagecolorat($my_img, $x,$y) == $line_colour) {
                $x++; $y++;
            }
            if($w->id_zdroj == 2) {
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
        imagestring($my_img, 3, 20, 9, ($timenew - $timeold)*1000, $text_colour);
        header( "Content-type: image/png" );
        imagepng( $my_img );
        imagecolordeallocate( $my_img,$line_colour );
        imagecolordeallocate($my_img ,$text_colour );
        imagecolordeallocate( $my_img,$background );
        imagedestroy( $my_img );
    }
}