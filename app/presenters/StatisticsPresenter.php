<?php

namespace App\Presenters;

use App\Service\StatisticsManager;
use App\Service\WifiSecurityService;
use Nette,
	App\Model;


class StatisticsPresenter extends BasePresenter {

	/** @var StatisticsManager @inject */
	public $statisticsManager;

	/** @var WifiSecurityService @inject */
	public $wsservice;

	/** Statistics page */
	public function renderDefault() {
		$this->template->allSecurityTypes = $this->wsservice->getAllWifiSecurityTypes();
		$this->template->actualStatistics = $this->statisticsManager->getLatestStatistics();
		$this->template->secondLatestStatistics = $this->statisticsManager->getSecondLatestStatistics();
		$this->template->allStatistics = $this->statisticsManager->getAllStatistics();
	}

	/**
	 * create statistics in db
	 * @throws Nette\Application\AbortException
	 */
	public function renderCreateStatistics() {
		if($this->statisticsManager->getCurrentStatistics()) {
			echo "statistika již existuje";
			$this->terminate();
		}

		$this->statisticsManager->createStatistics();
		echo "Statistika byla vytvořena";
		$this->terminate();
	}

}
