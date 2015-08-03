<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:10
 */
namespace App\Model;
use Nette;

class WifiManager extends Nette\Object {

	// polomer kruznice vytvorene z bodu kliknuti
	const CLICK_POINT_CIRCLE_RADIUS = 0.03;
	private $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

	/**
	 * sestavi dotaz s omezenim rozsahu latitude a longitude<br>
	 * -> vytvori select *<br>
	 * pro zmenu selectu na vysledku funkce zavolat ->select("sloupec1,sloupec2,..")
	 * 		- zde nutnost vyjmenovat vsechny potrebne sloupce
	 *
	 * @param float $lat1
	 * @param float $lat2
	 * @param float $lon1
	 * @param float $lon2
	 * @return Nette\Database\Table\Selection
	 */
	private function getNetsRangeQuery($lat1,$lat2,$lon1,$lon2) {
		$q = $this->database->table("wifi")
			->where("latitude < ?", $lat2)
			->where("latitude > ?", $lat1)
			->where("longitude < ?", $lon2)
			->where("longitude > ?", $lon1);
		return $q;
	}


	/**
	 * sestavi dotaz pro vyhledavani, pomoci parametru sestavi filtr
	 *
	 * @param float $lat1 nejmensi latitude
	 * @param float $lat2 nejvetsi latitude
	 * @param float $lon1 nejmensi longitude
	 * @param float $lon2 nejvetsilongitude
	 * @param array $params asociativni pole parametru
	 * @return Nette\Database\Table\Selection
	 */
	private function getSearchQuery($lat1,$lat2,$lon1,$lon2,$params) {

		$q = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
		$q->select("latitude,longitude,ssid,mac,id_source");
		$q->where("ssid LIKE ?", "%".$params["ssid"]."%");
		return $q;
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
		$q = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
		return $q->fetchAll();
	}

	/**
	 * vrati vyhledavane site podle zadanych parametru
	 *
	 * @param float $lat1 nejmensi latitude
	 * @param float $lat2 nejvetsi latitude
	 * @param float $lon1 nejmensi longitude
	 * @param float $lon2 nejvetsi longitude
	 * @param array $params asociativni pole parametru
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getNetsModeSearch($lat1,$lat2,$lon1,$lon2,$params) {
		$q = $this->getSearchQuery($lat1,$lat2,$lon1,$lon2,$params);
		return $q->fetchAll();
	}

	public function getNetByIdJSON($id) {
		$w = $this->database->table("wifi")->select("id,channel,latitude,longitude,altitude,mac,ssid")->where("id = ?",$id)->fetch();
		return json_encode(array("detail"=>$w->toArray()));
	}



	public function getNetsProcessClick(Nette\Http\Request $request) {
		// vypocet rozdilu latitude a longitude


		$click_lat = doubleval($request->getQuery("click_lat"));
		$click_lon = doubleval($request->getQuery("click_lon"));
		$map_lat1 = doubleval($request->getQuery("map_lat1"));
		$map_lat2 = doubleval($request->getQuery("map_lat2"));
		$map_lon1 = doubleval($request->getQuery("map_lon1"));
		$map_lon2 = doubleval($request->getQuery("map_lon2"));

		if($map_lat2 < $map_lat1) {
			$tmp = $map_lat1;
			$map_lat1 = $map_lat2;
			$map_lat2 = $tmp;
		}
		if($map_lon2 < $map_lon1) {
			$tmp = $map_lon1;
			$map_lon1 = $map_lon2;
			$map_lon2 = $tmp;
		}

		$params = array("ssid"=>$request->getQuery("ssid"));


		$deltaLon = abs($map_lon2 - $map_lon1);
		$deltaLat = abs($map_lat2 - $map_lat1);

		// vytvoreni okoli bodu kliknuti
		$lat1 = (doubleval($click_lat) - self::CLICK_POINT_CIRCLE_RADIUS * $deltaLat);
		$lat2 = (doubleval($click_lat) + self::CLICK_POINT_CIRCLE_RADIUS * $deltaLat);

		$lon1 = (doubleval($click_lon) - self::CLICK_POINT_CIRCLE_RADIUS * $deltaLon);
		$lon2 = (doubleval($click_lon) + self::CLICK_POINT_CIRCLE_RADIUS * $deltaLon);

		$sql = $this->getSearchQuery($lat1,$lat2,$lon1,$lon2,$params);
		$sql->select("id,channel,altitude,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
		$sql->order("distance");

		$wf = $sql->fetchAll();

		$array = array();
		$array["count"] = count($wf);
		$array["detail"] = $sql->fetch()->toArray();


		/*dump($wf);
		dump(array_slice($wf,1,5,true));*/

		foreach(array_slice($wf,1,5,true) as $key=>$w) {
			/*$wifi = new Wifi();
			$wifi->setLatitude($w->latitude);
			$wifi->setLongitude($w->longitude);
			$wifi->setSsid($w->ssid);
			$wifi->setMac($w->mac);
			$wifi->setChannel($w->channel);
			$wifi->setAltitude($w->altitude);*/
			if($key != $array["detail"]["id"]) {
				$wa = $w->toArray();
				$p = array("id"=>$wa["id"],"ssid"=>$wa["ssid"]);
				$array["others"][] = $p;
			}

		}

		return json_encode($array);
	}

}