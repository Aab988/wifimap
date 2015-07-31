<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:10
 */
namespace App\Model;
use Nette;

class WifiManager extends Nette\Object {

	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}


	/**
	 * vrati data siti v dane plose
	 *
	 * @param float $lat1 nejmensi latitude
	 * @param float $lat2 nejvyssi latitude
	 * @param float $lon1 nejmensi longitude
	 * @param float $lon2 nejvyssi longitude
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getAllNetsInLatLngRange($lat1,$lat2,$lon1,$lon2) {

		$q = $this->database->table("wifi")->select("latitude,longitude,ssid,mac,id_source")
			->where("latitude < ?",$lat2)
			->where("latitude > ?",$lat1)
			->where("longitude < ?",$lon2)
			->where("longitude > ?",$lon1);


		return $q->fetchAll();
	}

	// params = pole klic hodnota + pripadne OR/AND info - vymyslet jak toto resit
	public function getNetsModeSearch($lat1,$lat2,$lon1,$lon2,$params) {
		$q = $this->database->table("wifi")->select("latitude,longitude,ssid,mac,id_source")
			->where("latitude < ?",$lat2)
			->where("latitude > ?",$lat1)
			->where("longitude < ?",$lon2)
			->where("longitude > ?",$lon1)
			->where("ssid LIKE ?", "%".$params["ssid"]."%");


		return $q->fetchAll();
	}


	public function getNetsProcessClick($click_lat, $click_lon, $map_lat1, $map_lat2, $map_lon1, $map_lon2) {
		$deltaLon = $map_lon2 - $map_lon1;
		if($map_lon2 < $map_lon1) {
			$deltaLon = $map_lon1 - $map_lon2;
		}

		$deltaLat = $map_lat2 - $map_lat1;
		if($map_lat2 < $map_lat1) {
			$deltaLat = $map_lat1 - $map_lat2;
		}

		$lat1 = (doubleval($click_lat)-0.03*$deltaLat);
		$lat2 = (doubleval($click_lat)+0.03*$deltaLat);

		if($lat1 > $lat2) {
			$pom = $lat2;
			$lat2 = $lat1;
			$lat1 = $pom;
		}

		$lon1 = (doubleval($click_lon)-0.03*$deltaLon);
		$lon2 = (doubleval($click_lon)+0.03*$deltaLon);

		if($lon1 > $lon2) {
			$pom = $lon1;
			$lon1 = $lon2;
			$lon2 = $pom;
		}

		$sql = "select latitude,longitude,ssid,mac,altitude,channel, SQRT(POW(latitude-$click_lat,2)+POW(longitude-$click_lon,2)) as distance
                from wifi
                where latitude > $lat1 and latitude < $lat2 and longitude > $lon1 and longitude < $lon2
                order by distance";

		$wf = $this->database->query($sql);
		$array = array();
		foreach($wf as $key=>$w) {
			$wifi = new Wifi();
			$wifi->setLatitude($w->latitude);
			$wifi->setLongitude($w->longitude);
			$wifi->setSsid($w->ssid);
			$wifi->setMac($w->mac);
			$wifi->setChannel($w->channel);
			$wifi->setAltitude($w->altitude);

			$array[] = $wifi;
		}
		return $array;
	}


}