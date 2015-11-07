<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Base presenter for all application presenters.
 */
class BasePresenter extends Nette\Application\UI\Presenter
{

	public function startup() {
		parent::startup();

		$this->template->isMapPage = ($this->getPresenter()->getName() == "Homepage");
	}


}
