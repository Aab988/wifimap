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

	/**
	 * rozdeli danou plochu na mensi plochy podle hustoty siti v miste
	 *
	 * @param $start_lat pocatecni latitude
	 * @param $end_lat koncova latitude
	 * @param $start_lon pocatecni longitude
	 * @param $end_lon koncova longitude
	 */
    public function renderPrepareWigleDownload($start_lat, $end_lat, $start_lon, $end_lon) {
		// ceska republika rozsah
		//$this->wigleDownload->generateLatLngDownloadArray(48.54570549184746,51.055207338584964,12.073974609375,18.8525390625);

		//hk rozsah
		$this->wigleDownload->generateLatLngDownloadArray(50.17074134967256,50.263887540074116,15.745468139648438,15.90545654296875);


		$this->terminate();
    }
    
}