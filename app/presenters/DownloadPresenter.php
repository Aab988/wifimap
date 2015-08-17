<?php

namespace App\Presenters;

use \App\Model\WigleDownload, \App\Model\WifileaksDownload, \Nette\Http;

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



	public function __construct(WifileaksDownload $wifileaksDownloadModel, WigleDownload $wigleDownloadModel) {
        $this->wigleDownload = $wigleDownloadModel;
        $this->wifileaksDownload = $wifileaksDownloadModel;
    }
    
    public function renderWigle() {
        $this->wigleDownload->download();
    }
    
    public function renderWifileaks() {
        $this->wifileaksDownload->download("../temp/wifileaks.tsv");
        
    }


    public function renderPrepareWigleDownload($lat_start,$lat_end,$lon_start,$lon_end) {
        $lat_start = doubleval($lat_start); $lat_end = doubleval($lat_end);
        $lon_start = doubleval($lon_start); $lon_end = doubleval($lon_end);
        if($lat_end < $lat_start) {
            $tmp = $lat_start;	$lat_start = $lat_end;	$lat_end = $tmp;
        }
        if($lon_end < $lon_start) {
            $tmp = $lon_start;	$lon_start = $lon_end;	$lon_end = $tmp;
        }

		// ceska republika rozsah
		//$this->wigleDownload->generateLatLngDownloadArray(48.54570549184746,51.055207338584964,12.073974609375,18.8525390625);

		//hk rozsah
		$this->wigleDownload->generateLatLngDownloadArray(50.17074134967256,50.263887540074116,15.745468139648438,15.90545654296875);

        // $this->wigleDownload->generateLatLngDownloadArray($lat_start,$lat_end,$lon_start,$lon_end);
		$this->terminate();
    }



    public function renderAddWigleRequest($lat1,$lat2,$lon1,$lon2) {
        echo "wigle request";

        echo $this->wigleDownload->processWigleRequestCreation($lat1,$lat2,$lon1,$lon2);
        $this->terminate();
    }

}