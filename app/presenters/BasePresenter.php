<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
class BasePresenter extends Nette\Application\UI\Presenter {

	/** all APs without filter */
	const MODE_ALL = "MODE_ALL";
	/** filtered APs */
	const MODE_SEARCH = "MODE_SEARCH";
	/** highlighted APs by params */
	const MODE_HIGHLIGHT = "MODE_HIGHLIGHT";
	/** only one AP */
	const MODE_ONE = 'MODE_ONE';
	/** calculated position */
	const MODE_CALCULATED = 'MODE_CALCULATED';

	/** default mode if its not set */
	const DEFAULT_MODE = self::MODE_ALL;

	/** use cache? */
	const CACHE_ON = false;

	const IMG_CACHE_DIR = "../temp/img_cache";


	/** @var array $cacheExpire expiration by zoom, index = zoom, value = seconds */
	protected static $cacheExpire = array(0,1,2,3,4,5,6,7,8,9=>86400, // 1 day
		10=>57600, // 16 hours
		11,12=>28800, // 8 hours
		13=>14400, // 4 hours
		14,15=>7200, // 2hours
		16,17,18=>3600, // 1 hour
		19,20,21 => 1800); // 30 minutes

	/** @var Nette\Caching\Cache */
	protected $cache;

	/** @var \App\Service\Logger @inject */
	public $logger;

	/**
	 * initial config
	 */
	public function __construct() {
		parent::__construct();
		if (self::CACHE_ON) {
			if(!file_exists(self::IMG_CACHE_DIR)) {
				mkdir(self::IMG_CACHE_DIR);
			}
			$storage = new Nette\Caching\Storages\FileStorage(self::IMG_CACHE_DIR);
			$this->cache = new Nette\Caching\Cache($storage);
		}

	}

	/**
	 * set basic template vars available everywhere
	 */
	public function startup() {
		parent::startup();
		$this->template->isMapPage = ($this->getPresenter()->getName() == "Homepage");
		$this->template->actualPage = $this->getPresenter()->getName();
	}

	/**
	 * @param Model\Coords|null $coords
	 * @param string	$mode
	 * @param array	$array
	 * @return array
	 */
	protected function getParamsArray(Model\Coords $coords = null,$mode = self::MODE_ALL,$array) {
		if($coords == null) $coords = new Model\Coords($array["lat1"], $array["lat2"], $array["lon1"], $array["lon2"]);

		$params = array("coords" => $coords);
		// podle nastaveneho modu rozhodnout
		switch ($mode) {
			case WifiPresenter::MODE_SEARCH:
				$ssidmac =  (isset($array["ssidmac"])) ? $ssidmac = $array["ssidmac"] : null;
				if ($ssidmac) {
						if (Model\MyUtils::isMacAddress($ssidmac)) $params['mac'] = urldecode($ssidmac);
						else $params['ssid'] = $ssidmac;
				}
				$channel = (isset($array['channel'])) ? $array['channel'] : null;
				if ($channel != null && $channel != "") $params['channel'] = intval($channel);

				$security = (isset($array['security'])) ? $array['security'] : null;
				if ($security != null && $security != '') $params['sec'] = intval($security);

				$source = (isset($array['source'])) ? $array['source'] : null;
				if ($source != null && $source != "")$params['id_source'] = intval($source);

				break;
			case WifiPresenter::MODE_ONE:
				$params['ssid'] = isset($array["ssid"]) ? $array["ssid"] : "";
				break;
			default:
				break;
		}
		return $params;
	}

}
