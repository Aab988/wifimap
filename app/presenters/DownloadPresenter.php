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
	 * @param $start_lon pocatecni longtitude
	 * @param $end_lon koncova longtitude
	 */
    public function renderPrepareWigleDownload($start_lat, $end_lat, $start_lon, $end_lon) {

		$url = "https://wigle.net/gps/gps/GPSDB/onlinemap2/";

		if($end_lat < $start_lat) {
			$tmp = $start_lat;
			$start_lat = $end_lat;
			$end_lat = $tmp;
		}

		if($end_lon < $start_lon) {
			$tmp = $start_lon;
			$start_lon = $end_lon;
			$end_lon = $tmp;
		}

		$url.= "?lat1=$start_lat&long1=$start_lon&lat2=$end_lat&long2=$end_lon";
		$url.= "&redir=Y&networksOnly=Y&sizeX=256&sizeY=256";

		//header('Content-Type: image/png');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
		$a = curl_exec($ch); // $a will contain all headers

		//dump($a);

		$a = explode("-url:",$a);
		$b = explode("Locat",$a[1]);
		//dump($b[1]);

		$url = "https://wigle.net".trim($b[0]);
		//echo $url;
		$a = imagecreatefrompng($url);

		$points = 0;
		for($x = 0; $x< 256; $x++) {
			for($y = 0; $y<256; $y++) {
				if(dechex(imagecolorat($a, $x,$y)) != "7a000000") {
					$points++;
				}
				//echo dechex(imagecolorat($a, $x,$y)) . '<br />';

			}
		}

		echo $points;
		//imagepng($a);



		// priklad adresy
		// https://wigle.net/gps/gps//GPSDB/onlinemap2/?lat1=50.21909462044748&long1=15.787353515625
		//	&lat2=50.21206446065373&long2=15.79833984375&redir=Y&networksOnly=Y&sizeX=256&sizeY=256


		$this->terminate();
    }
    
}