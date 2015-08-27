<?php
/**
 * User: Roman
 * Date: 31.07.2015
 * Time: 10:10
 */
namespace App\Model;
use App\Presenters\WifiPresenter;
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
	 * @param Coords $coords
	 * @return Nette\Database\Table\Selection
	 */
	private function getNetsRangeQuery($coords) {
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
		$q->where("ssid LIKE ?", "%".$params["ssid"]."%");
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
				$params = array("ssid"=>$request->getQuery("ssid"));
				$sql = $this->getSearchQuery($requestCoords,$params);
				break;
			case WifiPresenter::MODE_ONE_SOURCE:
				$srca = explode("-",$request->getQuery("source"));
				$source = (isset($srca[1]))?intval($srca[1]):-1;
				$sql = $this->getOneSourceQuery($requestCoords,$source);
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

		return json_encode($json);
	}




}