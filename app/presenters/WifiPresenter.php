<?php

namespace App\Presenters;

use App\Model\Coords;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Service\OverlayRenderer;
use App\Service\WifiManager;
use Nette;
use Nette\Caching\Cache;

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
    const MODE_FREE = "MODE_FREE";

    /** default mode if its not set */
    const DEFAULT_MODE = self::MODE_ALL;

    /** can highlight by these params */
    const MODE_HIGHLIGHT_ALLOWED_BY = array('ssid', 'mac', 'channel');

    /** increasing image latlng range and image size */
    const INCREASE_LATLNG_RANGE_ABOUT = 0.125;

    /** use cache? */
    const CACHE_ON = true;

    /** @var array $cacheExpire expiration by zoom, index = zoom, value = seconds */
    private static $cacheExpire = array(0,1,2,3,4,5,6,7,8,9=>86400, // 1 day
                                        10=>57600, // 16 hours
                                        11,12=>28800, // 8 hours
                                        13=>14400, // 4 hours
                                        14,15=>7200, // 2hours
                                        16,17,18=>3600, // 1 hour
                                        19,20,21 => 1800); // 30 minutes

    const IMG_CACHE_DIR = "../temp/img_cache";


    const MIN_OVERLAY_ZOOM = 10;
    const MIN_INFO_WINDOW_ZOOM = 10;

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

    /** @var Cache */
    private $cache;
    /** @var array modes that can be used */
    private $allowedModes = array();

    public function __construct()
    {
        if (self::CACHE_ON) {
            if(!file_exists(self::IMG_CACHE_DIR)) {
                mkdir(self::IMG_CACHE_DIR);
            }
            $storage = new Nette\Caching\Storages\FileStorage(self::IMG_CACHE_DIR);
            $this->cache = new Cache($storage);
        }
        // ZAPNUTE MODY (TURNED ON MODES)
        $this->allowedModes = array(
            self::MODE_SEARCH,
            self::MODE_HIGHLIGHT,
            self::MODE_ALL,
            self::MODE_FREE,
            self::MODE_ONE_SOURCE
        );
    }


    public function renderProcessClick()
    {
        $httpr = $this->getHttpRequest();
        // pokud je nedostatecny zoom vratit prazdny - nemusim resit -> JS omezeni


        $r = $this->wifiManager->getClickQueryByMode($httpr);
        $detail = null;
        if ($httpr->getQuery("net")) {
            $net = intval($httpr->getQuery("net"));
            $detail = $this->wifiManager->getWifiById($net);
            $r = $this->wifiManager->getClickQueryByMode($httpr, $detail->latitude, $detail->longitude);
        } else {
            $f = $r->fetch();
            if ($f) {
                $detail = Wifi::createWifiFromDBRow($f);
            }
        }
        $json = array();
        $others = $r->fetchAll();
        $this->template->count = count($others);

        if ($detail) {
            unset($others[$detail->getId()]);
            $json['lat'] = $detail->getLatitude();
            $json['lng'] = $detail->getLongitude();
        }
        $this->template->setFile(__DIR__ . "/../templates/Wifi/processClick.latte");
        $this->template->detail = $detail;
        $this->template->others = $others;
        $temp = (string)$this->template;
        $json['iw'] = $temp;
        if($detail == null) {
            $json['success'] = false;
        }
        else {
            $json['success'] = true;
        }
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
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
        header("Content-type: image/png");
        DownloadPresenter::setIni(180, '1024M');
        $request = $this->getHttpRequest();

        // uzivatel se pokusil do url zadat kravinu prepnu na defaultni mod
        // kontrola kvuli cache key -> aby mi nemohl ulozit na server nejaky skodlivy kod
        if(!$this->allowedMode($mode)) $mode = self::DEFAULT_MODE;

        $zoom = intval($request->getQuery("zoom"));

        if($zoom < self::MIN_OVERLAY_ZOOM) {
            $img = $this->overlayRenderer->drawNone();
            echo MyUtils::image2string($img);
            return;
        }

        $coords = new Coords($lat1, $lat2, $lon1, $lon2);

        $coords->increaseLatRange(self::INCREASE_LATLNG_RANGE_ABOUT);
        $coords->increaseLonRange(self::INCREASE_LATLNG_RANGE_ABOUT);

        // params for image creation
        $params = array();

        switch($mode) {
            case self::MODE_SEARCH:
                $ssidmac = $request->getQuery("ssidmac");
                if($ssidmac) {
                    if(MyUtils::isMacAddress($ssidmac)) { $params['mac'] = urldecode($ssidmac); }
                    else { $params['ssid'] = $ssidmac; }
                }
                $channel = $request->getQuery('channel');
                if ($channel != null && $channel != "") { $params['channel'] = intval($channel); }
                $security = $request->getQuery('security');
                if($security != null && $security != '') { $params['sec'] = intval($security); }
                $source = $request->getQuery('source');
                if($source != null && $source != "") {$params['id_source'] = intval($source);}
                break;
            case self::MODE_HIGHLIGHT:
                $by = $request->getQuery("by");
                if (in_array($by, self::MODE_HIGHLIGHT_ALLOWED_BY)) {
                    $params['by'] = $by;
                    $val = $request->getQuery("val");
                    $params['val'] = $val;
                }
                break;
            case self::MODE_ONE_SOURCE:
                $srca = explode("-", $this->getHttpRequest()->getQuery("source"));
                $source = (isset($srca[1])) ? intval($srca[1]) : 0;
                $params['source'] = $source;
                break;
            case self::MODE_FREE:
                break;
            default:

                break;
        }
        $key = MyUtils::generateCacheKey($mode,$coords,$zoom,$params);

        if(self::CACHE_ON) {
            $img = $this->cache->load($key);
            if($img != null) {
                echo $img;
                return;
            }
        }

        switch($mode) {
            case self::MODE_SEARCH:
                $nets = $this->wifiManager->getNetsModeSearch($coords, $params);
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
            case self::MODE_HIGHLIGHT:
                if(!empty($params)) {
                    $highlitedNets = $this->wifiManager->getNetsBySt($coords, $params['by'], $params['val']);
                    $allNets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                    $img = $this->overlayRenderer->drawModeHighlight($coords, $zoom, $allNets, $highlitedNets);
                }
                else {
                    $nets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                    $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                }
                break;
            case self::MODE_ONE_SOURCE:
                $nets = $this->wifiManager->getNetsModeOneSource($coords, $params['source']);
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
            case self::MODE_FREE:
                $nets = $this->wifiManager->getFreeNets($coords);
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
            default:
                $nets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                $img = $this->overlayRenderer->drawModeAll($coords, $zoom, $nets);
                break;
        }

        $image = MyUtils::image2string($img);
        if(self::CACHE_ON) {
            $this->cache->save($key, $image, array(Cache::EXPIRE => time() + self::$cacheExpire[$zoom]));
        }
        echo $image;
        return;
    }




    private function allowedMode($mode) {
        return in_array($mode,$this->allowedModes);
    }

}