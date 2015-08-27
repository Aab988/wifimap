<?php

namespace App\Presenters;

use App\Model\Coords;
use App\Model\OverlayRenderer;
use App\Model\WifiManager;
use Nette;

//use Nette\Caching\Cache;

class WifiPresenter extends BasePresenter
{

    // MODES
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

    const MODE_ALL = "MODE_ALL";
    const MODE_SEARCH = "MODE_SEARCH";
    const MODE_HIGHLIGHT = "MODE_HIGHLIGHT";
    const MODE_ONE_SOURCE = "MODE_ONE_SOURCE";

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
    /* public function __construct() {
        // cache
        $storage = new Nette\Caching\Storages\FileStorage('../temp/sql_cache');
        $this->cache = new Cache($storage);
    } */


    public function renderProcessClick()
    {
        echo $this->wifiManager->getNetsProcessClick($this->getHttpRequest());
        $this->terminate();
    }

    /**
     * @param $lat1
     * @param $lat2
     * @param $lon1
     * @param $lon2
     * @param $zoom
     *
     * @deprecated
     */
    public function renderJson($lat1, $lat2, $lon1, $lon2, $zoom)
    {

        // spocitam rozdil latitud a longtitud
        $dlat = doubleval($lat2) - doubleval($lat1);
        $dlon = doubleval($lon2) - doubleval($lon1);
        if ($dlat < 0) $dlat = -$dlat;
        if ($dlon < 0) $dlon = -$dlon;

        // zvetsim nacitanou plochu
        $lat1c = $lat1 - (0 * $dlat);
        $lat2c = $lat2 + (0 * $dlat);

        $lon1c = $lon1 - (0 * $dlon);
        $lon2c = $lon2 + (0 * $dlon);

        $sql = "select latitude,longitude,ssid,mac from wifi where latitude > $lat1c and latitude < $lat2c and longitude > $lon1c and longitude < $lon2c";

        if ($zoom < 19) {
            $sql .= " limit 500";
        }

        // key pro cache
        /*$key = round($lat1c, 3) . round($lat2c,3) . round($lon1c,3) . round($lon2c,3) . $zoom;

        $value = $this->cache->load($key);
        if ($value === NULL) {*/
        $wf = $this->database->query($sql);
        $array = array();
        foreach ($wf as $w) {
            $a = array("ssid" => $w->ssid, "latitude" => $w->latitude, "longitude" => $w->longitude, "mac" => $w->mac);
            $array[] = $a;
        }

        //$this->cache->save($key, $array, array(Cache::EXPIRE => '10 minutes'));
        /*  }
          else { $array = $value; }*/
        echo json_encode($array);

    }


    public function renderImage($mode, $lat1, $lat2, $lon1, $lon2)
    {
        DownloadPresenter::setIni(180, '512M');

        $zoom = intval($this->getHttpRequest()->getQuery("zoom"));
        $coords = new Coords($lat1, $lat2, $lon1, $lon2);

        $coords->increaseLatRange(0.125);
        $coords->increaseLonRange(0.125);

        switch ($mode) {
            case self::MODE_SEARCH:
                // vyhledavani
                $ssid = $this->getHttpRequest()->getQuery("ssid");
                $nets = $this->wifiManager->getNetsModeSearch($coords, array("ssid" => $ssid));
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
            case self::MODE_HIGHLIGHT:
                $ssid = $this->getHttpRequest()->getQuery("ssid");

                $allNets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                $highlitedNets = $this->wifiManager->getNetsModeSearch($coords, array("ssid" => $ssid));
                $img = $this->overlayRenderer->drawModeHighlight($coords, $zoom, $allNets, $highlitedNets);
                break;
            case self::MODE_ONE_SOURCE:
                $srca = explode("-",$this->getHttpRequest()->getQuery("source"));
                // id Source (-1 = neznamy,takze se zobrazi oba)
                $source = (isset($srca[1]))?intval($srca[1]):0;
                $nets = $this->wifiManager->getNetsModeOneSource($coords,$source);
                $img = $this->overlayRenderer->drawModeAll($coords,$zoom,$nets);
                break;
            default:
                $nets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
        }
        header("Content-type: image/png");
        imagepng($img);

    }

}