<?php

namespace App\Presenters;

use \App\Model\Coords;
use App\Model\Wifi;
use \App\Service;
use Nette\Http\Url;
use Nette\Http\UrlScript;

class DownloadPresenter extends BasePresenter {

    const FROM_TEMP_DIR_KEY = 'fromtempdir';
    /** @var \App\Service\WigleDownload @inject */
    public $wigleDownload;
    /** @var \App\Service\WifileaksDownload @inject */
    public $wifileaksDownload;
    /** @var Service\GoogleDownload @inject */
    public $googleDownload;
    /** @var \App\Service\DownloadRequest @inject */
    public $wigleRequest;
    /** @var \App\Service\WigleDownloadQueue @inject */
    public $downloadQueue;
    /** @var \App\Service\WifiManager @inject */
    public $wifiManager;

    /**
     * CRON - 50times a day
     * process Wigle Download
     * @throws \Nette\Application\AbortException
     */
    public function renderWigle() {
        if($this->wigleDownload) {
            $this->wigleDownload->downloadQueue = $this->downloadQueue;
            $this->wigleDownload->download();
        }
		$this->terminate();
    }

    /**
     * process Wifileaks file parse and save to DB
     * @throws \Nette\Application\AbortException
     */
    public function renderWifileaks() {
        self::setIni(1800,'512M');
        $fromtempdir = $this->getHttpRequest()->getQuery(self::FROM_TEMP_DIR_KEY);
        if($fromtempdir) {
            //TODO:
            // naj�t soubory v tempu
            // pokud n�jaky odpovida regularu na nazev wifileaks souboru tak vzit (nejlepe ten nejnov�j�i)
            $this->wifileaksDownload->download("../temp/wifileaks.tsv");
        }
        else {
            $this->wifileaksDownload->download();
        }
		$this->terminate();
    }

    /**
     * create google request -> save it to db
     */
    public function renderCreateGoogleRequest() {
        $this->googleDownload->setWifiManager($this->wifiManager);
        $req = $this->getHttpRequest();
        if($req->getQuery("wid")) {
            $wifi = $this->wifiManager->getWifiById($req->getQuery("wid"));
            $this->googleDownload->createRequestFromWifi($wifi);
        }
        $this->terminate();
    }


    public function renderGoogle() {
        if(!$this->googleDownload) return;

        $this->googleDownload->setWifiManager($this->wifiManager);

        // tohle by slo udelat jako bin soubor

        // vzit z databaze z fronty zaznam (nahodny) - aby kdyz se nedari jeden stahnout tak aby mi to neskoncilo
        // stahnout info z google
        // pokud mam dobrou presnost tak ulozit a nastavit ze stazeno
        // jinak neukladat -> zalogovat pokus o stazeni


        // https://maps.googleapis.com/maps/api/browserlocation/json?browser=firefox&sensor=true&wifi=mac:00-0c-42-2b-44-ac|ssid:DivecAirNetZapadWPA|ss:80wifi=mac:00-0c-42-23-1a-0e|ssid:MedAirNet|ss:20
        $click_lat = 50.19069300754107;
        $click_lon = 15.804262161254883;

        $wfs = $this->wifiManager->get2ClosestWifiToCoords(Coords::createCoordsRangeByLatLng($click_lat,$click_lon,0.03));
        dump($wfs);

        $wifi = Wifi::createWifiFromAssociativeArray($wfs[array_keys($wfs)[0]]);
        dump($wifi);



        $this->terminate();
    }

    /**
     * set script variables
     *
     * @param int $max_execution_time number of seconds
     * @param string $max_memory fe: '256M'
     *
     */
    public static function setIni($max_execution_time,$max_memory) {
        if(ini_get('safe_mode')) {
            //echo "safe mode is on";
        }
        else {
            //echo "Safe mode is off";
            $tl = set_time_limit($max_execution_time);
            if(!$tl) {
            //echo "<br />nepovedlo se zvysit timelimit";
            }
            $is = ini_set('memory_limit',$max_memory);
            if(!$is) {
                            //echo "<br />nepovedlo se zvysit maximum memory";
            }
        }

    }


    public function renderPrepareWigleDownload($lat_start,$lat_end,$lon_start,$lon_end) {
        $coords = new Coords($lat_start,$lat_end,$lon_start,$lon_end);

		// ceska republika rozsah
		//$this->wigleDownload->generateLatLngDownloadArray(48.54570549184746,51.055207338584964,12.073974609375,18.8525390625);

		//hk rozsah
		//$this->wigleDownload->generateLatLngDownloadArray(new Coords(50.17074134967256,50.263887540074116,15.745468139648438,15.90545654296875));

        // $this->wigleDownload->generateLatLngDownloadArray($lat_start,$lat_end,$lon_start,$lon_end);
		$this->terminate();
    }


    /**
     * add wigle request guide
     * finaly create wigle request
     */
    public function renderAddWigleRequest() {
        if($this->getHttpRequest()->getQuery("show") == "HELP") {
            $this->template->setFile( __DIR__. "/../templates/Download/wigle_create_request_info.latte");
			return;
        }
		else {
			$state = $this->wigleRequest->processDownloadRequestCreation(new Coords(
                $this->getHttpRequest()->getQuery("lat1"),
                $this->getHttpRequest()->getQuery("lat2"),
                $this->getHttpRequest()->getQuery("lon1"),
                $this->getHttpRequest()->getQuery("lon2")
            ),Service\WigleDownload::ID_SOURCE);
			$this->template->setFile( __DIR__. "/../templates/Download/info_wigle_req.latte");

            $this->template->code = $state;
		}
    }


    public function actionAddGoogleRequest() {
        if($this->getHttpRequest()->getQuery("show") == "HELP") {
            $this->view = 'addgooglerequesthelp';
        }
        else {
            $this->view = 'addgooglerequest';
        }
    }

    public function renderAddGoogleRequestHelp() {
    }

    public function renderAddGoogleRequest() {
        $this->googleDownload->setWifiManager($this->wifiManager);
        $req = $this->getHttpRequest();
        $coords = new Coords(
            $req->getQuery("lat1"),
            $req->getQuery("lat2"),
            $req->getQuery("lon1"),
            $req->getQuery("lon2")
        );
        $state = $this->googleDownload->createRequestFromArea($coords);
        $this->template->state = $state;
    }

    /**
     * CRON -> run every 1 HOUR (?)
     *
     * get one use created DownloadRequest, divide it by wifi density and add calculated records to wigle download queue
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderProcessWigleRequest() {

        self::setIni(1200, '256M');
        $req = $this->wigleRequest->getEldestDownloadRequest(Service\WigleDownload::ID_SOURCE);
        $coords = new Coords($req->lat_start,$req->lat_end,$req->lon_start,$req->lon_end);
        $this->downloadQueue->generateLatLngDownloadArray($coords);
        $this->downloadQueue->save();
        $this->wigleRequest->setProcessed($req);
        $this->terminate();
    }


}