<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:10
 */
namespace App\Service;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Presenters\BasePresenter;
use App\Presenters\WifiPresenter;
use App\Model\Coords;

use Nette;

class WifiManager extends BaseService {

	// polomer kruznice vytvorene z bodu kliknuti
	const CLICK_POINT_CIRCLE_RADIUS = 0.03;

	const TABLE = 'wifi';

	/**
	 * get all wifi sites by params
	 *
	 * @param array $params
	 * @param int $limit
	 * @param int|null $offset
	 * @return array|Nette\Database\Table\IRow[]
	 */
	public function getNetsByParams($params,$limit = 10000,$offset = null) {
		$q = $this->database->table('wifi');
		$q->select('*');
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
		if($limit) $q->limit($limit, $offset);
		return $q->fetchAll();
	}

	/**
	 * @param $params
	 * @return int
	 */
	public function getNetsCountByParams($params) {
		$q = $this->database->table('wifi');
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
		return $q->count('id');
	}

	/**
	 * create query with latitude and longitude range
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

	/**
	 * builds SQL select
	 *
	 * @param array $select
	 * @return string
	 */
	private function buildSelect($select = array('*')) {
		$sqlSelect = '*';
		if($select != null) {
			$select2 = array();
			foreach($select as $s) {
				if($s!='') $select2[] = $s;
			}
			$sqlSelect = implode(',',$select2);
		}
		return $sqlSelect;
	}

	/**
	 * @param Coords $coords
	 * @param int $id_source
	 * @return Nette\Database\Table\Selection
	 */
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
	 * @param array $select
	 * @param bool $asArray
	 * @return Wifi[]
	 */
	public function getAllNetsInLatLngRange($coords, $select = array('*'), $asArray = false) {

		$sqlSelect = $this->buildSelect($select);
		$sql = 'SELECT ' . $sqlSelect . ' FROM ' . self::TABLE . ' WHERE  (`latitude` > ?) AND (`latitude` < ?) AND (`longitude` > ?) AND (`longitude` < ?)';

		$pdo = $this->database->getConnection()->getPdo();
		$sth = $pdo->prepare($sql);
		$sth->execute(array($coords->getLatStart(),$coords->getLatEnd(),$coords->getLonStart(),$coords->getLonEnd()));

		$data = $sth->fetchAll(\PDO::FETCH_ASSOC);

		// return as array of Objects
		if(!$asArray) {
			$wifi = array();
			foreach($data as $w) {
				$wifi[] = Wifi::createWifiFromDBRow($w);
			}
			$data = $wifi;
		}
		return $data;
	}

	/**
	 * return searched nets by params
	 *
	 * @param Coords $coords
	 * @param array $params associative array with params
	 * @return Wifi[]
	 */
	public function getNetsModeSearch($coords,$params) {
		$q = $this->getSearchQuery($coords,$params);
		return Wifi::createWifiArrayFromDBRowArray($q->fetchAll());
	}

	/**
	 * @param Coords $coords
	 * @param string $param
	 * @param string $value
	 * @return \App\Model\Wifi[]
	 */
	public function getNetsBySt($coords,$param, $value) {
		$q = $this->getNetsRangeQuery($coords);
		$q->where($param,$value);
		return Wifi::createWifiArrayFromDBRowArray($q->fetchAll());
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

	/**
	 * @param Coords $coords
	 * @return Nette\Database\Table\Selection
	 */
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
	 * return one wifi by ID
	 *
	 * @param int $id
	 * @return Wifi
	 */
	public function getWifiById($id) {
		return Wifi::createWifiFromDBRow($this->database->table("wifi")->where("id",$id)->fetch());
	}

	/**
	 * @param Nette\Http\IRequest $request
	 * @param null|float                $click_lat
	 * @param null|float                $click_lon
	 * @return Nette\Database\Table\Selection
	 */
	public function getClickQueryByMode(Nette\Http\IRequest $request, $click_lat = null, $click_lon = null) {
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
			case WifiPresenter::MODE_SEARCH:
				$params = array();
				if ($request->getQuery("ssidmac")) {
					if(MyUtils::isMacAddress($request->getQuery("ssidmac"))) {
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
				if($request->getQuery("source")!=null && $request->getQuery("source") != "") {
					$params['id_source'] = intval($request->getQuery("source"));
				}
				$sql = $this->getSearchQuery($requestCoords,$params);
				break;
			case WifiPresenter::MODE_ONE:
				$params['ssid'] = $request->getQuery('ssid');
				$sql = $this->getSearchQuery($requestCoords,$params);
				break;
			default:
				$sql = $this->getNetsRangeQuery($requestCoords);
		}
		$sql->select("*,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
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
			$detail = $this->getWifiById($id);

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
			default:
				$sql = $this->getNetsRangeQuery($requestCoords);
		}

		$sql->select("id,mac,latitude,longitude,ssid,channel,altitude,SQRT(POW(latitude-?,2)+POW(longitude-?,2)) AS distance ",doubleval($click_lat),doubleval($click_lon));
		$sql->order("distance");

		if(!$detail) {
			$f = $sql->fetch();
			if($f) {
				$detail = Wifi::createWifiFromDBRow($f);
			}
		}

		$wf = $sql->fetchPairs("id");
		$nbs = $wf;
		unset($nbs[$detail->getId()]);

		$json = array();
		$json["count"] = count($wf);
		$json["detail"] = $detail;
		foreach(array_slice($nbs,0,5,true) as $nb) {
			$json["others"][] = $nb->toArray();
		}

		return $json;
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
			->where('channel > 0')
			->order('channel ASC')
			->fetchAll();
	}

	/**
	 * @param Wifi $wifi
	 * @return Wifi
	 */
	public function getClosestWifiToWifi(Wifi $wifi) {
		$coords = Coords::createCoordsRangeByLatLng($wifi->getLatitude(),$wifi->getLongitude(),0.03);
		return Wifi::createWifiFromDBRow($this->getNetsRangeQuery($coords)
			->where("id != ?", $wifi->getId())
			->where('mac != ?',$wifi->getMac())
			->order("SQRT(POW(latitude-?,2)+POW(longitude-?,2))",$wifi->getLatitude(),$wifi->getLongitude())
			->limit(1)->fetch());
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

	/**
	 * @param int $limit
	 * @param int $from
	 * @return \App\Model\Wifi[]
	 */
	public function getAllNets($limit=null,$from=null) {
		$q = $this->database->table('wifi');
		if($limit) $q->where('id > ?',$from)->limit(intval($limit));
		$w = $q->fetchAll();
		return Wifi::createWifiArrayFromDBRowArray($w);
	}

	/**
	 * @param string $mac
	 * @param float $r_latitude
	 * @param float $r_longitude
	 * @return array|Nette\Database\IRow[]
	 */
	public function getDistanceFromOriginalGPS($mac,$r_latitude,$r_longitude) {
		$q = "select w.id_source,w.id,w.mac,w.ssid,w.latitude,w.longitude, s.name, SQRT(POW(w.latitude-?,2)+POW(w.longitude-?,2)) as distance, w.calculated
			  from wifi w
			  join source s on (s.id = w.id_source)
			  where w.mac LIKE ?
			  order by distance ASC";
		$all = $this->database->query($q,$r_latitude,$r_longitude,$mac)->fetchAll();
		return $all;
	}



}