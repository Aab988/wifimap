<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
    
	public $database;
	
	public function __construct(Nette\Database\Context $database) {
	    $this->database = $database;
	}
        
        

	public function renderDefault()
	{


	}
}
