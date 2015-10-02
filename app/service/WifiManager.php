<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:10
 */
namespace App\Service;
use App\Model\Wifi;
use App\Presenters\WifiPresenter;
use App\Model\Coords;

use Nette;

class WifiManager extends BaseService {

	// polomer kruznice vytvorene z bodu kliknuti
	const CLICK_POINT_CIRCLE_RADIUS = 0.03;


	/**
	 * create query with latitude and longitude range<br>
	 *
	 * @param Coords $coords
	 * @return Nette\Database\Table\Selection
	 */
	public function getNetsRangeQuery($coords) {
		$q = $this->database->table("wifi")
			->where("latitude < ?", $coords->getLatEnd())
			->where("latitude > ?", $coords->getLatStart())
			->where("longitude < ?", $coords->getLonEnd())
			->where("longitude > ?", $coords->getLonStart());
		return $q;
	}


	/**
	 * create search query by associative params array
	 *
	 * @param Coords $coords
	 * @param array $params associative array with params ($param => $value)
	 * @return Nette\Database\Table\Selection
	 */
	private function getSearchQuery($coords,$params) {

		$q = $this->getNetsRangeQuery($coords);
		$q->select("id,latitude,longitude,ssid,mac,id_source");
		foreach($params as $param => $val) {
			switch($param) {
				case 'ssid':
					$q->where("$param LIKE ?","%$val%"); break;
				case 'mac':
					$mac = str_replace("-",":",$val);
					$q->where("$param LIKE ?","%$mac%"); break;
				default:
					$q->where($param,$val); break;
			}
		}
		return $q;
	}

	private function getOneSourceQuery($coords,$id_source) {
		$q = $this->getNetsRangeQuery($coords);
		if($id_source > 0) {
			$q->where("id_source",$id_source);
		}
		return $q;
	}


	/**
	 * return nets data in passed lat lng range
	 *
	 * @param Coords $coords
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getAllNetsInLatLngRange($coords) {
		$q = $this->getNetsRangeQuery($coords);
		return $q->fetchAll();
	}

	/**
	 * return searched nets by params
	 *
	 * @param Coords $coords
	 * @param array $params associative array with params
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getNetsModeSearch($coords,$params) {
		$q = $this->getSearchQuery($coords,$params);
		return $q->fetchAll();
	}



	public function getNetsBySt($coords,$param, $value) {
		$q = $this->getNetsRangeQuery($coords);
		$q->where($param,$value);
		return $q->fetchAll();
	}

	/**
	 * return nets for one source mode
	 *
	 * @param Coords $coords
	 * @param int $source_id
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getNetsModeOneSource($coords,$source_id) {

		return $this->getOneSourceQuery($coords,$source_id)->fetchAll();

	}

	private function getFreeNetsQuery($coords) {
		$q = $this->getNetsRangeQuery($coords);
		$q->where('sec = ? OR freenet = ?',1,'Y');
		return $q;
	}

	/**
	 * return all free nets
	 *
	 * @param Coords $coords
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getFreeNets($coords) {
		return $this->getFreeNetsQuery($coords)->fetchAll();
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



	public function getClickQueryByMode(Nette\Http\Request $request, $click_lat = null, $click_lon = null) {
		if(!$click_lat && !$click_lon) {
			$click_lat = doubleval($request->getQuery("click_lat"));
			$click_lon = doubleval($request->getQuery("click_lon"));
		}
		$mapCoords = new Coords($request->getQuery("map_lat1"),$request->getQuery("map_lat2"),$request->getQuery("map_lon1"),$request->getQuery("map_lon2"));

		$lat1 = (doubleval($click_lat) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());
		$lat2 = (doubleval($click_lat) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());
		$lon1 = (doubleval($click_lon) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());
		$lon2 = (doubleval($click_lon) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());

		$requestCoords = new Coords($lat1,$lat2,$lon1,$lon2);

		switch($request->getQuery("mode")) {
			case WifiPresenter::MODE_HIGHLIGHT:
				$sql = $this->getNetsRangeQuery($requestCoords);
				break;

			case WifiPresenter::MODE_SEARCH:
				$params = array();
				if ($request->getQuery("ssidmac")) {
					if(preg_match("^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})^",urldecode($request->getQuery("ssidmac")))) {
						$params['mac'] = urldecode($request->getQuery("ssidmac"));
					}
					else {
						$params["ssid"] = $request->getQuery("ssidmac");
					}
				}
				if($request->getQuery("channel")!=null && $request->getQuery("channel") != "") {
					$params['channel'] = intval($request->getQuery("channel"));
				}
				if($request->getQuery("security")!=null && $request->getQuery("security") != "") {
					$params['sec'] = intval($request->getQuery("security"));
				}
				$sql = $this->getSearchQuery($requestCoords,$params);
				break;
			case WifiPresenter::MODE_ONE_SOURCE:
				$srca = explode("-",$request->getQuery("source"));
				$source = (isset($srca[1]))?intval($srca[1]):-1;
				$sql = $this->getOneSourceQuery($requestCoords,$source);
				break;
			case WifiPresenter::MODE_FREE:
				$sql = $this->getFreeNetsQuery($requestCoords);
				break;
			default:
				$sql = $this->getNetsRangeQuery($requestCoords);
		}
		$sql->select("id,mac,date_added,sec,channel,freenet,paynet,latitude,longitude,ssid,channel,altitude,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
		$sql->order("distance");
		return $sql;
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
		$mapCoords = new Coords($request->getQuery("map_lat1"),$request->getQuery("map_lat2"),$request->getQuery("map_lon1"),$request->getQuery("map_lon2"));

		// vytvoreni okoli bodu kliknuti
		$lat1 = (doubleval($click_lat) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());
		$lat2 = (doubleval($click_lat) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLat());

		$lon1 = (doubleval($click_lon) - self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());
		$lon2 = (doubleval($click_lon) + self::CLICK_POINT_CIRCLE_RADIUS * $mapCoords->getDeltaLon());

		$requestCoords = new Coords($lat1,$lat2,$lon1,$lon2);

		switch($request->getQuery("mode")) {
			case WifiPresenter::MODE_HIGHLIGHT:
				$sql = $this->getNetsRangeQuery($requestCoords);
				break;

			case WifiPresenter::MODE_SEARCH:
				$params = array();
				if ($request->getQuery("ssidmac")) {
					if(preg_match("^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})^",urldecode($request->getQuery("ssidmac")))) {
						$params['mac'] = urldecode($request->getQuery("ssidmac"));
					}
					else {
						$params["ssid"] = $request->getQuery("ssidmac");
					}
				}
				if($request->getQuery("channel") && $request->getQuery("channel") != "") {
					$params['channel'] = intval($request->getQuery("channel"));
				}
				if($request->getQuery("security") && $request->getQuery("security") != "") {
					$params['sec'] = intval($request->getQuery("security"));
				}
				$sql = $this->getSearchQuery($requestCoords,$params);
				break;
			case WifiPresenter::MODE_ONE_SOURCE:
				$srca = explode("-",$request->getQuery("source"));
				$source = (isset($srca[1]))?intval($srca[1]):-1;
				$sql = $this->getOneSourceQuery($requestCoords,$source);
				break;
			case WifiPresenter::MODE_FREE:
				$sql = $this->getFreeNetsQuery($requestCoords);
				break;
			default:
				$sql = $this->getNetsRangeQuery($requestCoords);
		}

		$sql->select("id,mac,latitude,longitude,ssid,channel,altitude,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
		$sql->order("distance");

		if(!$detail) {
			$f = $sql->fetch();
			if($f) {
				$detail = $f->toArray();
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

		return $json;
		//return json_encode($json);
	}


	/**
	 * get all channels that are used
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getAllChannels()
	{
		return $this->database->table("wifi")
			->select("DISTINCT channel")
			->where("channel IS NOT NULL")
			->fetchAll();
	}


	/**
	 * @param Wifi $wifi
	 * @return bool|mixed|Nette\Database\Table\IRow
	 */
	public function getClosestWifiToWifi(Wifi $wifi) {
		$coords = Coords::createCoordsRangeByLatLng($wifi->getLatitude(),$wifi->getLongitude(),0.03);
		return $this->getNetsRangeQuery($coords)
			->order("SQRT(POW(latitude-?,2)+POW(longitude-?,2))",$wifi->getLatitude(),$wifi->getLongitude())
			->limit(1)->fetch();
	}

	/**
	 * @param Coords $coords
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function get2ClosestWifiToCoords(Coords $coords) {
		return $this->getNetsRangeQuery($coords)
			->order("SQRT(POW(latitude-?,2)+POW(longitude-?,2))",$coords->getCenterLat(),$coords->getCenterLng())
			->limit(2)->fetchAll();
	}






}