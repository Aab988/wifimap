<?php

namespace App\Presenters;

use App\Model\Coords;
use \App\Model\WigleDownload, \App\Model\WifileaksDownload, \Nette\Http;
use App\Model\WigleRequest;

class DownloadPresenter extends BasePresenter {
    
    /**
     *
     * @var WigleDownload
     * @inject
     */
    public $wigleDownload;
    
    /**
     *
     * @var WifileaksDownload
     * @inject
     */
    public $wifileaksDownload;

    /**
     * @var WigleRequest
     * @inject
     */
    public $wigleRequest;


    public function renderWigle() {
        $this->wigleDownload->download();
		$this->terminate();
    }
    
    public function renderWifileaks() {
        $this->wifileaksDownload->download("../temp/wifileaks.tsv");
		$this->terminate();
        
    }


    public function renderPrepareWigleDownload($lat_start,$lat_end,$lon_start,$lon_end) {
        $coords = new Coords($lat_start,$lat_end,$lon_start,$lon_end);

		// ceska republika rozsah
		//$this->wigleDownload->generateLatLngDownloadArray(48.54570549184746,51.055207338584964,12.073974609375,18.8525390625);

		//hk rozsah
		$this->wigleDownload->generateLatLngDownloadArray(new Coords(50.17074134967256,50.263887540074116,15.745468139648438,15.90545654296875));

        // $this->wigleDownload->generateLatLngDownloadArray($lat_start,$lat_end,$lon_start,$lon_end);
		$this->terminate();
    }



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


    public function renderRectGen() {
        $lat1 = 50.21605376832277;
        $lat2 = 50.23606150790367;
        $lon1 = 15.801542195377579;
        $lon2 = 15.840568481184846;

        $this->wigleRequest->findNotInQueueRectsInLatLngRange(new Coords($lat1,$lat2,$lon1,$lon2));
        $this->terminate();
    }

}