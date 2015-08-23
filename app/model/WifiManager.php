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
	 * create query with latitude and longitude range<br>
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
	 * create search query by associative params array
	 *
	 * @param float $lat1
	 * @param float $lat2
	 * @param float $lon1
	 * @param float $lon2
	 * @param array $params associative array with params ($param => $value)
	 * @return Nette\Database\Table\Selection
	 */
	private function getSearchQuery($lat1,$lat2,$lon1,$lon2,$params) {

		$q = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
		$q->select("id,latitude,longitude,ssid,mac,id_source");
		$q->where("ssid LIKE ?", "%".$params["ssid"]."%");
		return $q;
	}


	/**
	 * return nets data in passed lat lng range
	 *
	 * @param float $lat1
	 * @param float $lat2
	 * @param float $lon1
	 * @param float $lon2
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getAllNetsInLatLngRange($lat1,$lat2,$lon1,$lon2) {
		$q = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
		return $q->fetchAll();
	}

	/**
	 * return searched nets by params
	 *
	 * @param float $lat1
	 * @param float $lat2
	 * @param float $lon1
	 * @param float $lon2
	 * @param array $params associative array with params
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getNetsModeSearch($lat1,$lat2,$lon1,$lon2,$params) {
		$q = $this->getSearchQuery($lat1,$lat2,$lon1,$lon2,$params);
		return $q->fetchAll();
	}

	/**
	 * return one net details by ID
	 *
	 * @param $id
	 * @return bool|mixed|Nette\Database\Table\IRow
	 */
	public function getDetailById($id) {
		return $this->database->table("wifi")->where("id",$id)->fetch();
	}

	/**
	 *	get JSON with nets - use MODE and get only sites which are visible in that MODES
	 *
	 * @param Nette\Http\Request $request
	 * @return string JSON formated array
	 */
	public function getNetsProcessClick(Nette\Http\Request $request) {

		$detail = false;

		if($request->getQuery("net")) {
			$id = intval($request->getQuery("net"));
			$detail = $this->getDetailById($id)->toArray();

			$click_lat = $detail["latitude"];
			$click_lon = $detail["longitude"];
		}
		else {
			$click_lat = doubleval($request->getQuery("click_lat"));
			$click_lon = doubleval($request->getQuery("click_lon"));
		}


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

		$deltaLon = abs($map_lon2 - $map_lon1);
		$deltaLat = abs($map_lat2 - $map_lat1);

		// vytvoreni okoli bodu kliknuti
		$lat1 = (doubleval($click_lat) - self::CLICK_POINT_CIRCLE_RADIUS * $deltaLat);
		$lat2 = (doubleval($click_lat) + self::CLICK_POINT_CIRCLE_RADIUS * $deltaLat);

		$lon1 = (doubleval($click_lon) - self::CLICK_POINT_CIRCLE_RADIUS * $deltaLon);
		$lon2 = (doubleval($click_lon) + self::CLICK_POINT_CIRCLE_RADIUS * $deltaLon);



		switch($request->getQuery("mode")) {
			case 'MODE_HIGHLIGHT':
				$sql = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
				break;

			case 'MODE_SEARCH':
				$params = array("ssid"=>$request->getQuery("ssid"));
				$sql = $this->getSearchQuery($lat1,$lat2,$lon1,$lon2,$params);
				break;

			default:
				$sql = $this->getNetsRangeQuery($lat1,$lat2,$lon1,$lon2);
		}


		$sql->select("id,mac,latitude,longitude,ssid,channel,altitude,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
		$sql->order("distance");


		if(!$detail) {
			if($sql->fetch()) {
				$detail = $sql->fetch()->toArray();
			}
		}


		$wf = $sql->fetchPairs("id");
		$nbs = $wf;
		unset($nbs[$detail["id"]]);

		$json = array();
		$json["count"] = count($wf);
		$json["detail"] = $detail;
		foreach(array_slice($nbs,0,5,true) as $nb) {
			$json["others"][] = $nb->toArray();
		}



		return json_encode($json);
	}

}