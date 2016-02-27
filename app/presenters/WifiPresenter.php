<?php

namespace App\Presenters;

use App\Model\Coords;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Service\OptimizedWifiManager;
use App\Service\OverlayRenderer;
use App\Service\SourceManager;
use App\Service\WifiManager;
use App\Service\WifiSecurityService;
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

        $r = $this->wifiManager->getClickQueryByMode($httpr);
        $detail = null;
        if ($httpr->getQuery("net")) {
            $net = intval($httpr->getQuery("net"));
            $detail = $this->wifiManager->getWifiById($net);
            $detail->setSec($this->wifisecService->getById($detail->getSec()));
            $r = $this->wifiManager->getClickQueryByMode($httpr, $detail->getLatitude(), $detail->getLongitude());
        } else {
            $f = $r->fetch();
            if ($f) {
                $detail = Wifi::createWifiFromDBRow($f);
                $detail->setSec($this->wifisecService->getById($f->sec));
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

        // TODO: pokud detail je null osetrit

        //$detail->setSec($this->wifisecService->getById($detail->getSec()));
        $detail->setSource($this->sourceManager->getById($detail->getSource()));
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
        if(self::CACHE_ON) {
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
        if(self::CACHE_ON) {
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
        unset($params['id']); unset($params['gm']);unset($params['action']);
        $this->template->parameters = $params;
    }

}