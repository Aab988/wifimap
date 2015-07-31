<?php

namespace App\Presenters;
use App\Model\OverlayRenderer;
use App\Model\WifiManager;
use Nette;
//use Nette\Caching\Cache;

class WifiPresenter extends BasePresenter {

    /**
     * @var WifiManager
     * @inject
     */
    public $wifiManager;

    /**
     * @var OverlayRenderer
     * @inject
     */
    public $overlayRenderer;

   // private $cache;
    // TODO: podle aktualniho modu vybirat a vracet/zobrazovat data (aby se po kliknuti nezobrazovali i jine site než jsou vlastnì aktualnì vidìt)


    /*
    public function __construct() {
        // cache
        $storage = new Nette\Caching\Storages\FileStorage('../temp/sql_cache');
        $this->cache = new Cache($storage);
    }
    */

    public function renderProcessClick($click_lat, $click_lon, $map_lat1, $map_lat2, $map_lon1, $map_lon2) {
        // dostane MÓD -> podle nìj si vytáhne daná data a vykreslí
        $mode = $this->getHttpRequest()->getQuery("mode");
        switch($mode) {
            case 'MODE_SEARCH':
                // TODO: search mode
                break;
			default:
				// mode ALL
				break;
        }
        $this->template->nets = $this->wifiManager->getNetsProcessClick($click_lat, $click_lon, $map_lat1, $map_lat2, $map_lon1, $map_lon2);
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

    /*
     * MODY
     *
     * MODE_ALL - vsechny site
     * MODE_ONE_SOURCE - pouze jeden zdroj
     * MODE_SEARCH - vyhledavani
     * MODE_HIGHLIGHT - oznaceni podle parametru
     * MODE_FREE - pouze volne site
     * MODE_ONE - jedna sit
     * MODE_CALCULATED_POSITION - vypoctena pozice site
     *
     */

    /// TODO: v kazdym case mit jen volani jedne funkce z modelu -> predat vsechny parametry a model uz si prebere co vyuzije a co ne
    public function renderImage($mode, $lat1, $lat2, $lon1, $lon2) {

        switch($mode) {
            case 'MODE_SEARCH':
                // vyhledavani
				$ssid = $this->getHttpRequest()->getQuery("ssid");
				$nets = $this->wifiManager->getNetsModeSearch($lat1,$lat2,$lon1,$lon2,array("ssid"=>$ssid));
				$zoom = intval($this->getHttpRequest()->getQuery("zoom"));
				$img = $this->overlayRenderer->drawModeAll($lat1,$lat2,$lon1,$lon2,$zoom,$nets);
                break;

            default:
                // normalni zobrazeni - vsechny site
				$lat1 = doubleval($lat1); $lat2 = doubleval($lat2);
				$lon1 = doubleval($lon1); $lon2 = doubleval($lon2);

				if($lat2 < $lat1) { $pom = $lat1; $lat1 = $lat2; $lat2 = $pom;}
				if($lon2 < $lon1) { $pom = $lon1; $lon1 = $lon2; $lon2 = $pom;}

				$nets = $this->wifiManager->getAllNetsInLatLngRange($lat1,$lat2,$lon1,$lon2);
				$zoom = intval($this->getHttpRequest()->getQuery("zoom"));
				$img = $this->overlayRenderer->drawModeAll($lat1,$lat2,$lon1,$lon2,$zoom,$nets);
                break;
        }
		header( "Content-type: image/png" );
		imagepng( $img );
    }

}