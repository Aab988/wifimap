<?php

namespace App\Presenters;

use App\Model\ArrayUtil;
use App\Model\Coords;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Service\GoogleDownload;
use App\Service\OptimizedWifiManager;
use App\Service\OverlayRenderer;
use App\Service\SourceManager;
use App\Service\WifiManager;
use App\Service\WifiSecurityService;
use App\Service\WigleDownload;
use Nette;
use Nette\Caching\Cache;
use Tracy\Debugger;

class WifiPresenter extends BasePresenter
{
    /** all APs without filter */
    const MODE_ALL = "MODE_ALL";
    /** filtered APs */
    const MODE_SEARCH = "MODE_SEARCH";
    /** highlighted APs by params */
    const MODE_HIGHLIGHT = "MODE_HIGHLIGHT";
    /** only one AP */
    const MODE_ONE = 'MODE_ONE';
    /** calculated position */
    const MODE_CALCULATED = 'MODE_CALCULATED';

    /** default mode if its not set */
    const DEFAULT_MODE = self::MODE_ALL;


    /** increasing image latlng range and image size */
    const INCREASE_LATLNG_RANGE_ABOUT = 0.125;

    const MIN_OVERLAY_ZOOM = 10;
    const MIN_INFO_WINDOW_ZOOM = 10;

    /** polomer kruznice vytvorene z bodu kliknuti */
    const CLICK_POINT_CIRCLE_RADIUS = 0.03;

    /** @var WifiManager @inject */
    public $wifiManager;

    /** @var OverlayRenderer */
    public $overlayRenderer;

    /** @var SourceManager @inject */
    public $sourceManager;

    /** @var WifiSecurityService @inject */
    public $wifisecService;

    /** @var OptimizedWifiManager @inject */
    public $oWifiManager;

    /** @var array modes that can be used */
    private $allowedModes = array();

    private static $modesLabels = array(
        self::MODE_ALL => "Všechny sítě",
        self::MODE_SEARCH => "Vyhledávání",
        self::MODE_HIGHLIGHT => "Zvýraznění bodu v mapě",
        self::MODE_ONE => "Jedna síť",
        self::MODE_CALCULATED => "Vypočtené polohy sítí"
    );

    /** can highlight by these params */
    public static $MODE_HIGHLIGHT_ALLOWED_BY = array('ssid', 'mac', 'channel');

    public function __construct()
    {
        parent::__construct();
        $this->allowedModes = array(
            self::MODE_SEARCH,
            self::MODE_HIGHLIGHT,
            self::MODE_ALL,
            self::MODE_ONE,
            self::MODE_CALCULATED
        );
    }

    /**
     * show detail of one AP
     * @throws Nette\Application\AbortException
     */
    public function renderProcessClick()
    {
        $httpr = $this->getHttpRequest();
        // pokud je nedostatecny zoom vratit prazdny - nemusim resit -> JS omezeni
        if($httpr->getQuery("zoom") != null && $httpr->getQuery("zoom") < self::MIN_INFO_WINDOW_ZOOM) {
            echo json_encode(array("success"=>false), JSON_UNESCAPED_UNICODE);
            $this->terminate();
        }

        $click_lat = doubleval($httpr->getQuery("click_lat"));
        $click_lon = doubleval($httpr->getQuery("click_lon"));

        $detail = array();
        // pokud jiz je konkretni sit (prekliknuto z IW)
        if($httpr->getQuery("net")) {
            $id = intval($httpr->getQuery("net"));
            $wifi = $this->wifiManager->getWifiById($id);

            $click_lat = $wifi->getLatitude();
            $click_lon = $wifi->getLongitude();

            $detail["id"] = $wifi->getId();
            $detail["mac"] = $wifi->getMac();
            $detail["latitude"] = $wifi->getLatitude();
            $detail["longitude"] = $wifi->getLongitude();
            $detail["ssid"] = $wifi->getSsid();
            $detail["channel"] = $wifi->getChannel();
            $detail["altitude"] = $wifi->getAltitude();
            $detail["calculated"] = $wifi->getCalculated();
            $detail["dateAdded"] = $wifi->getDateAdded();
            $detail["accuracy"] = $wifi->getAccuracy();
            $detail["sec"]["label"] = $this->wifisecService->getById($wifi->getSec())->getLabel();
            $source = $this->sourceManager->getById($wifi->getSource());
            $detail["source"]["id"] = $source["id"];
            $detail["source"]["name"] = $source["name"];
        }

        $mapCoords = new Coords($httpr->getQuery("map_lat1"),$httpr->getQuery("map_lat2"),$httpr->getQuery("map_lon1"),$httpr->getQuery("map_lon2"));

        $lat1 = (doubleval($click_lat) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());
        $lat2 = (doubleval($click_lat) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());
        $lon1 = (doubleval($click_lon) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());
        $lon2 = (doubleval($click_lon) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());

        $requestCoords = new Coords($lat1,$lat2,$lon1,$lon2);

        $params = array("coords" => $requestCoords);
        // podle nastaveneho modu rozhodnout
        switch($httpr->getQuery("mode")) {
            case self::MODE_SEARCH:
                if ($httpr->getQuery("ssidmac") != null) {
                    if(MyUtils::isMacAddress(urldecode($httpr->getQuery("ssidmac")))) { $params['mac'] = urldecode($httpr->getQuery("ssidmac")); }
                    else { $params["ssid"] = $httpr->getQuery("ssidmac"); }
                }
                if($httpr->getQuery("channel") != null && $httpr->getQuery("channel") != "") { $params['channel'] = intval($httpr->getQuery("channel")); }
                if($httpr->getQuery("security") != null && $httpr->getQuery("security") != "") { $params['sec'] = intval($httpr->getQuery("security")); }
                if($httpr->getQuery("source") != null && $httpr->getQuery("source") != "") { $params["id_source"] = intval($httpr->getQuery("source")); }
                break;
            case self::MODE_ONE:
                $params['ssid'] = $httpr->getQuery('ssid');
                break;
            default:
                break;
        }

        $select = array("id","mac","latitude","longitude","ssid","channel","altitude","calculated","date_added","accuracy","sec","id_source","SQRT(POW(latitude-".doubleval($click_lat).",2)+POW(longitude-".doubleval($click_lon).",2)) AS distance");

        $nets = $this->oWifiManager->getNetsByParams($params,$select,null,"distance");

        // neni to rozkliknuto
        if(!$httpr->getQuery("net") && isset($nets[0])) {
            $wifi = $nets[0];
            $detail["id"] = $wifi["id"];
            $detail["mac"] = $wifi["mac"];
            $detail["latitude"] = $wifi["latitude"];
            $detail["longitude"] = $wifi["longitude"];
            $detail["ssid"] = $wifi["ssid"];
            $detail["channel"] = $wifi["channel"];
            $detail["altitude"] = $wifi["altitude"];
            $detail["calculated"] = $wifi["calculated"];
            $detail["dateAdded"] = $wifi["date_added"];
            $detail["accuracy"] = $wifi["accuracy"];
            $detail["sec"]["label"] = $this->wifisecService->getById($wifi["sec"])->getLabel();
            $source = $this->sourceManager->getById($wifi["id_source"]);
            $detail["source"]["id"] = $source["id"];
            $detail["source"]["name"] = $source["name"];
        }

        $count = count($nets) - 1;
        $others = array();

        unset($nets[0]);
        foreach(array_slice($nets,0,5,true) as $w) {
            $others[] = array(
                'id' => $w["id"],
                'mac' => $w['mac'],
                'ssid' => $w['ssid']
            );
        }

        $json = array();
        $this->template->setFile(__DIR__ . "/../templates/Wifi/processClick.latte");
        $this->template->count = $count;
        $this->template->others = $others;
        $this->template->detail = $detail;
        $temp = (string)$this->template;

        $json['iw'] = $temp;

        if($detail == null) {
            $json['success'] = false;
        }
        else {
            $json['success'] = true;
            $json['lat'] = $detail['latitude'];
            $json['lng'] = $detail['longitude'];
        }

        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        $this->terminate();
    }

    /**
     * render one image for overlay
     *
     * @param string $mode
     * @param float $lat1
     * @param float $lat2
     * @param float $lon1
     * @param float $lon2
     */
    public function renderImage($mode, $lat1, $lat2, $lon1, $lon2)
    {
        //header("Content-type: image/png");
        MyUtils::setIni(180, '1024M');

        $request = $this->getHttpRequest();
        $zoom = $request->getQuery("zoom");

        $this->overlayRenderer = new OverlayRenderer($zoom);

        // uzivatel se pokusil do url zadat kravinu prepnu na defaultni mod
        // kontrola kvuli cache key -> aby mi nemohl ulozit na server nejaky skodlivy kod
        if(!$this->allowedMode($mode)) $mode = self::DEFAULT_MODE;

        // moc maly zoom vratim obrazek at si priblizi
        if($zoom < self::MIN_OVERLAY_ZOOM) {
            echo MyUtils::image2string($this->overlayRenderer->drawNone());
            exit;
        }

        // zvysim rozsah souradnic - kvuli orezavani
        $coords = new Coords($lat1, $lat2, $lon1, $lon2);
        $coords->increaseLatLngRange(self::INCREASE_LATLNG_RANGE_ABOUT);

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
                if (in_array($by, self::$MODE_HIGHLIGHT_ALLOWED_BY)) {
                    $params['by'] = $by;
                    $val = $request->getQuery("val");
                    $params['val'] = $val;
                }
                break;
            case self::MODE_ONE:
                $params['ssid'] = $this->getHttpRequest()->getQuery('ssid');
                break;
            default:
                break;
        }

        // vygenerovani klice pro cache
        $key = MyUtils::generateCacheKey($mode,$coords,$zoom,$params);

        // zkusi se nalezt v cache
        if(self::CACHE_ON && $mode != self::MODE_CALCULATED) {
            $img = $this->cache->load($key);
            if($img != null) {
                echo $img;
                return;
            }
        }

        // ziskani dat a vygenerovani prekryvne vrstvy

        $img = $this->overlayRenderer->drawNone();
        switch($mode) {
            case self::MODE_SEARCH:
                $params['coords'] = $coords;
                $nets = $this->oWifiManager->getNetsByParams($params,array('ssid,mac,latitude,longitude,id_source'));
                //$nets = $this->wifiManager->getNetsModeSearch($coords, $params);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                break;
            case self::MODE_HIGHLIGHT:
                if(!empty($params)) {
                    $params['coords'] = $coords;
                    $params[$params['by']] = $params['val'];
                    unset($params['by']); unset($params['val']);
                    $highlightedIds = $this->oWifiManager->getNetsByParams($params,array('id'));
                    $allNets = $this->oWifiManager->getNetsByParams(array('coords'=>$params['coords']),array('id,ssid,mac,latitude,longitude,id_source'));
                    $img = $this->overlayRenderer->drawModeHighlight($coords, $allNets, $highlightedIds);
                }
                else {
                    $nets = $this->wifiManager->getAllNetsInLatLngRange($coords,array('latitude','longitude','ssid','mac','id_source'),true);
                    $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                }
                break;
            case self::MODE_ONE:
                $nets = $this->wifiManager->getNetsModeSearch($coords,$params);
                $img = $this->overlayRenderer->drawModeOne($coords,$nets);
                break;
            case self::MODE_CALCULATED:
                $net = $this->wifiManager->getWifiById($this->getHttpRequest()->getQuery('a'));
                $lat = $net->getLatitude();
                $lon = $net->getLongitude();

                $lat1 = doubleval($lat) - 0.003;
                $lat2 = doubleval($lat) + 0.003;
                $lon1 = doubleval($lon) - 0.003/2;
                $lon2 = doubleval($lon) + 0.003/2;

                $coordsNew = new Coords($lat1,$lat2,$lon1,$lon2);

                $nets = $this->wifiManager->getNetsModeSearch($coordsNew, array('mac'=>$net->getMac()));
                $nets2 = $this->wifiManager->getNetsModeSearch($coords,array('mac'=>$net->getMac()));
                $latt = 0; $lont = 0;
                foreach($nets as $net) {
                    $latt += $net->getLatitude();
                    $lont += $net->getLongitude();
                }
                $lat_avg = $latt / ((double)count($nets));
                $lon_avg = $lont / ((double)count($nets));
                $net = new Wifi();
                $net->setLatitude($lat_avg);
                $net->setLongitude($lon_avg);
                $img = $this->overlayRenderer->drawCalculated($coords,$nets2,$net);
                break;
            default:

                $nets = $this->wifiManager->getAllNetsInLatLngRange($coords,array('latitude','longitude','ssid','mac','id_source'),true);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                break;

        }
        $image = MyUtils::image2string($img);
        $img = null;
        if(self::CACHE_ON && $mode != self::MODE_CALCULATED) {
            $this->cache->save($key, $image, array(Cache::EXPIRE => time() + self::$cacheExpire[$zoom]));
        }
        echo $image;
        return;
    }

    private function allowedMode($mode) {
        return in_array($mode,$this->allowedModes);
    }




    public function renderActualMode() {
        Debugger::$productionMode = true;
        $params = $this->request->getParameters();
        if(isset($params['security'])) $params['security'] = $this->wifisecService->getById(intval($params['security']))->getLabel();
        if(isset($params['mode'])) {
            $params['mode'] = (array_key_exists($params['mode'],self::$modesLabels)) ? self::$modesLabels[$params['mode']] : $params['mode'];
        }
        else {
            $params['mode'] = self::$modesLabels[self::DEFAULT_MODE];
        }
        if(isset($params['source'])) $params['source'] = ($this->sourceManager->getById(intval($params['source'])))?$this->sourceManager->getById(intval($params['source']))->name:$params['source'];
        if(isset($params['ssidmac'])) $params['ssidmac'] = urldecode($params['ssidmac']);

        unset($params['id']); unset($params['gm']);unset($params['action']);
        $this->template->parameters = $params;
    }

}