<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
class BasePresenter extends Nette\Application\UI\Presenter
{
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

	public function startup() {
		parent::startup();

		$this->template->isMapPage = ($this->getPresenter()->getName() == "Homepage");
	}


}
