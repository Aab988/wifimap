<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Application\UI;
use App\Service;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	/**
	 * @var Service\SourceManager
	 * @inject
	 */
	public $sourceManager;

	/**
	 * @var Service\WifiManager
	 * @inject
	 */
	public $wifiManager;


	public $database;
	
	public function __construct(Nette\Database\Context $database) {
	    $this->database = $database;
	}
        
        

	public function renderDefault()
	{
		$this->template->sources = $this->sourceManager->getAllSources();
	}


	protected function createComponentSearchForm() {
		$form = new UI\Form;

		$form->getElementPrototype()->class = "form-horizontal";
		$ssidmacTxt = $form->addText('ssidmac', 'SSID/MAC:');
		$ssidmacTxt->getControlPrototype()->class='form-control';;
		//$ssidmac->getControlPrototype()->id='ssidmac';
		$ssidmaclbl = $ssidmacTxt->getLabel()->addAttributes(array('class'=>'control-label col-sm-2'));

		$channelsQ = $this->wifiManager->getAllChannels();
		$channels = array();
		foreach($channelsQ as $ch) {
			$channels[$ch->channel] = $ch->channel;
		}

		$channelSelect = $form->addSelect('channel','Kanál:', $channels)
			->setPrompt('Všechny kanály')
			->getControlPrototype()->addAttributes(array('class'=>'form-control'));

		$securitySelect = $form->addSelect('security', 'Zabezpečení:', array('Otevřená', 'WEP', 'WPA1', 'WPA2', 'jiné'))
			->setPrompt("Všechny typy")
			->getControlPrototype()->addAttributes(array('class'=>'form-control'));

		$form->addSubmit('search', 'Vyhledat')->getControlPrototype()->addAttributes(array('class'=>'form-control btn btn-info btn-sm'));;
		//$form->getElementPrototype()->onsubmit = 'return searchFormSubmit()';
		//$form->getElementPrototype()->id = 'searchForm';
		return $form;
	}

}
