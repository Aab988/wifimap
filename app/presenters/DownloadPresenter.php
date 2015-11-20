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
    public $downloadRequest;
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
     * DOWNLOAD OBSERVATIONS OF MAC ADDRESS FROM WIGLE
     */
    public function renderWigleObservations() {

        if($this->wigleDownload) {
            $this->wigleDownload->downloadObservations();
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
            // najit soubory v tempu
            // pokud nejaky odpovida regularu na nazev wifileaks souboru tak vzit (nejlepe ten nejnovejsi)
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
        $this->googleDownload->download();
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
            set_time_limit($max_execution_time);
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
     * determine render action
     * @param $show
     */
    public function actionAddWigleRequest($show) {
        $this->determineView($show);
    }

    public function renderAddWigleRequestHelp() {}

    public function renderAddWigleRequest() {
        $state = $this->addRequest(Service\WigleDownload::ID_SOURCE);
        $this->template->state = $state;
    }


    /**
     * determine render action
     * @param string $show
     */
    public function actionAddGoogleRequest($show) {
        $this->determineView($show);
    }

    /** show help for google request */
    public function renderAddGoogleRequestHelp() {}

    /** add download request */
    public function renderAddGoogleRequest() {
        $state = $this->addRequest(Service\GoogleDownload::ID_SOURCE);
        $this->template->state = $state;
    }


    /**
     * add download request
     *
     * @param int $idSource
     * @return bool|string
     */
    private function addRequest($idSource) {
        return $this->downloadRequest->processDownloadRequestCreation(new Coords(
            $this->getHttpRequest()->getQuery("lat1"),
            $this->getHttpRequest()->getQuery("lat2"),
            $this->getHttpRequest()->getQuery("lon1"),
            $this->getHttpRequest()->getQuery("lon2")
        ),$idSource);
    }


    /**
     * determines what view show
     */
    private function determineView($show) {
        if(strcasecmp($show,'help') == 0) {
            $this->view = $this->getAction().'help';
        }
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
        $req = $this->downloadRequest->getEldestDownloadRequest(Service\WigleDownload::ID_SOURCE);
        $coords = new Coords($req->lat_start,$req->lat_end,$req->lon_start,$req->lon_end);
        $this->downloadQueue->generateLatLngDownloadArray($coords, $req->id);
        $total_count = count($this->downloadQueue->getGeneratedCoords());
        $this->downloadQueue->save($req->id);
        $this->downloadRequest->setProcessed($req,$total_count);
        $this->terminate();
    }


    /**
     * CRON - every 30 minutes
     *
     * get one user created DownloadRequest for google, and process it
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderProcessGoogleRequest() {
        $this->googleDownload->setWifiManager($this->wifiManager);
        $req = $this->downloadRequest->getEldestDownloadRequest(Service\GoogleDownload::ID_SOURCE);
        $coords = new Coords($req->lat_start,$req->lat_end,$req->lon_start,$req->lon_end);
        $this->googleDownload->createRequestFromArea($coords);
        $this->downloadRequest->setProcessed($req);
        $this->terminate();
    }

}