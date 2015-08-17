<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class StatisticsPresenter extends BasePresenter
{

	public $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}



	public function renderDefault()
	{



		//$this->template->wifi = $this->database->table("wifi")->where("id_zdroj=2")->order("rand()");
	}

}
