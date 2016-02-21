<?php

namespace App\Presenters;

use App\Model\Coords;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Service\OverlayRenderer;
use App\Service\SourceManager;
use App\Service\WifiLocationService;
use App\Service\WifiManager;
use App\Service\WifiSecurityService;
use Nette;
use Nette\Caching\Cache;
use Tracy\Debugger;

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
    const MODE_ONE = 'MODE_ONE';

    const MODE_CALCULATED = 'MODE_CALCULATED';

    /** default mode if its not set */
    const DEFAULT_MODE = self::MODE_ALL;

    /** can highlight by these params */
    const MODE_HIGHLIGHT_ALLOWED_BY = array('ssid', 'mac', 'channel');

    /** increasing image latlng range and image size */
    const INCREASE_LATLNG_RANGE_ABOUT = 0.125;

    /** use cache? */
    const CACHE_ON = false;

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

    /** @var WifiManager @inject */
    public $wifiManager;

    /** @var WifiLocationService @inject */
    public $wifiLocationService;

    /** @var OverlayRenderer */
    public $overlayRenderer;

    /** @var SourceManager @inject */
    public $sourceManager;

    /** @var WifiSecurityService @inject */
    public $wifisecService;

    /** @var Cache */
    private $cache;
    /** @var array modes that can be used */
    private $allowedModes = array();


    private static $modesLabels = array(
        self::MODE_ALL => "Všechny sítě",
        self::MODE_SEARCH => "Vyhledávání",
        self::MODE_HIGHLIGHT => "Zvýraznění bodu v mapě",
        self::MODE_FREE => "Nezabezpečené sítě",
        self::MODE_ONE_SOURCE => "Pouze jeden zdroj",
        self::MODE_ONE => "Jedna síť",
        self::MODE_CALCULATED => "Vypočtené polohy sítí"
    );


    public function __construct()
    {
        parent::__construct();
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
            self::MODE_ONE_SOURCE,
            self::MODE_ONE,
            self::MODE_CALCULATED
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

    public function renderImage($mode, $lat1, $lat2, $lon1, $lon2)
    {
        $time1 = microtime(true);
        //header("Content-type: image/png");
        //MyUtils::setIni(180, '2048M');

        $request = $this->getHttpRequest();
        $zoom = $request->getQuery("zoom");

        $this->overlayRenderer = new OverlayRenderer($zoom);

        // uzivatel se pokusil do url zadat kravinu prepnu na defaultni mod
        // kontrola kvuli cache key -> aby mi nemohl ulozit na server nejaky skodlivy kod
        if(!$this->allowedMode($mode)) $mode = self::DEFAULT_MODE;

        if($zoom < self::MIN_OVERLAY_ZOOM) {
            echo $this->overlayRenderer->drawNone()->toString(Nette\Utils\Image::PNG);
            exit;
        }

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
            case self::MODE_ONE:
                $params['ssid'] = $this->getHttpRequest()->getQuery('ssid');
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

        //echo "cas 1:" . ((microtime(true) - $time1) * 1000);
        $time1 = microtime(true);
        switch($mode) {
            case self::MODE_SEARCH:
                $nets = $this->wifiManager->getNetsModeSearch($coords, $params);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                break;
            case self::MODE_HIGHLIGHT:
                if(!empty($params)) {
                    $highlitedNets = $this->wifiManager->getNetsBySt($coords, $params['by'], $params['val']);
                    $allNets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                    $img = $this->overlayRenderer->drawModeHighlight($coords, $allNets, $highlitedNets);
                }
                else {
                    $nets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                    $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                }
                break;
            case self::MODE_ONE_SOURCE:
                $nets = $this->wifiManager->getNetsModeOneSource($coords, $params['source']);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                break;
            case self::MODE_FREE:
                $nets = $this->wifiManager->getFreeNets($coords);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                break;
            case self::MODE_ONE:
                $nets = $this->wifiManager->getNetsModeSearch($coords,$params);
                $img = $this->overlayRenderer->drawModeOne($coords,$nets);
                break;
            case self::MODE_CALCULATED:
                $net = $this->wifiManager->getWifiById($this->getHttpRequest()->getQuery('a'));
                $nets = $this->wifiLocationService->getLocation($net);
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

                $nets = $this->wifiManager->getAllNetsInLatLngRange($coords);
                //echo "cas 2:" . ((microtime(true) - $time1) * 1000);
                $time1 = microtime(true);
                $img = $this->overlayRenderer->drawModeAll($coords, $nets);
                //echo "cas 3:" . ((microtime(true) - $time1) * 1000);
                $time1 = microtime(true);
                break;

        }
        $time = microtime(true);
        //$img = $this->overlayRenderer->drawNone();
        $image = $img->toString(Nette\Utils\Image::PNG);
       // $image = MyUtils::image2string($img);
        $img = null;
        if(self::CACHE_ON) {
            $this->cache->save($key, $image, array(Cache::EXPIRE => time() + self::$cacheExpire[$zoom]));
        }
        echo $image;
        //echo "cas:" . ((microtime(true) - $time)*1000);
        echo "cas 4:" . ((microtime(true) - $time1) * 1000);
        return;
    }

    private function allowedMode($mode) {
        return in_array($mode,$this->allowedModes);
    }


    public function renderActualMode() {
        Debugger::$productionMode = true;
        $params = $this->request->getParameters();
        if(isset($params['security'])) $params['security'] = $this->wifisecService->getById(intval($params['security']))->getLabel();
        if(isset($params['mode'])) $params['mode'] = (array_key_exists($params['mode'],self::$modesLabels))?self::$modesLabels[$params['mode']]:$params['mode'];
        if(isset($params['source'])) $params['source'] = ($this->sourceManager->getById(intval($params['source'])))?$this->sourceManager->getById(intval($params['source']))->name:$params['source'];
        unset($params['id']); unset($params['gm']);unset($params['action']);
        $this->template->parameters = $params;
    }

}