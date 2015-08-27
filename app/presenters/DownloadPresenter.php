<?php

namespace App\Presenters;

use \App\Model\Coords;
use \App\Model\DownloadQueue;
use \App\Model\WigleDownload, \App\Model\WifileaksDownload, \Nette\Http;
use \App\Model\WigleRequest;

class DownloadPresenter extends BasePresenter {

    /**
     *
     * @var \App\Model\WigleDownload
     * @inject
     */
    public $wigleDownload;
    
    /**
     *
     * @var \App\Model\WifileaksDownload
     * @inject
     */
    public $wifileaksDownload;

    /**
     * @var \App\Model\WigleRequest
     * @inject
     */
    public $wigleRequest;

    /**
     * @var \App\Model\DownloadQueue
     * @inject
     */
    public $downloadQueue;


    /**
     * CRON - 50times a day
     * process Wigle Download
     * @throws \Nette\Application\AbortException
     */
    public function renderWigle() {
        $this->wigleDownload->download();
		$this->terminate();
    }

    /**
     * process Wifileaks file parse and save to DB
     * @throws \Nette\Application\AbortException
     */
    public function renderWifileaks() {
        self::setIni(600,'128M');
        $this->wifileaksDownload->download("../temp/wifileaks.tsv");
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
            echo "safe mode is on";
        }
        else {
            echo "Safe mode is off";
            $tl = set_time_limit($max_execution_time);
            if(!$tl) { echo "<br />nepovedlo se zvysit timelimit";}
            $is = ini_set('memory_limit',$max_memory);
            if(!$is) { echo "<br />nepovedlo se zvysit maximum memory";}
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
     *
     */
    public function renderAddWigleRequest() {

        if($this->getHttpRequest()->getQuery("show") == "HELP") {
            $this->template->setFile( __DIR__. "/../templates/Download/wigle_create_request_info.latte");
			return;
        }
		else {
			$state = $this->wigleRequest->processWigleRequestCreation(new Coords(
                    $this->getHttpRequest()->getQuery("lat1"),
                    $this->getHttpRequest()->getQuery("lat2"),
                    $this->getHttpRequest()->getQuery("lon1"),
                    $this->getHttpRequest()->getQuery("lon2")
            ));
			$this->template->setFile( __DIR__. "/../templates/Download/info_wigle_req.latte");

            $this->template->code = $state;
		}
    }

    /**
     * CRON -> run every 1 HOUR (?)
     *
     * get one use created WigleRequest, divide it by wifi density and add calculated records to wigle download queue
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderProcessWigleRequest() {

        self::setIni(1200, '256M');
        $req = $this->wigleRequest->getEldestWigleRequest();
        $coords = new Coords($req->lat_start,$req->lat_end,$req->lon_start,$req->lon_end);
        $this->downloadQueue->generateLatLngDownloadArray($coords);
        $this->downloadQueue->save();
        $this->wigleRequest->setProcessed($req);
        $this->terminate();
    }


}