<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Application\UI;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	/**
	 * @var Model\SourceManager
	 * @inject
	 */
	public $sourceManager;


	public $database;
	
	public function __construct(Nette\Database\Context $database) {
	    $this->database = $database;
	}
        
        

	public function renderDefault()
	{
		$this->template->sources = $this->sourceManager->getAllSources();
	}



	protected function createComponentRegistrationForm()
	{
		$form = new UI\Form;
		$form->addSelect('source', 'Vyberte zdroj:', $this->sourceManager->getAllSourcesAsKeyVal());
		$form->addSubmit("submit");
		$form->onSuccess[] = array($this, 'registrationFormSucceeded');
		return $form;
	}
}
