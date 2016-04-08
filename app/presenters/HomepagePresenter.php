<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Application\UI;
use App\Service;

/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter {

	/** @var Service\SourceManager @inject */
	public $sourceManager;

	/** @var Service\WifiManager @inject */
	public $wifiManager;

	/** @var Service\WifiSecurityService @inject */
	public $wifiSecurityService;

	/** MAP page */
	public function renderDefault() {
		$this->template->sources = $this->sourceManager->getAllSources();
	}

	/**
	 * search form
	 * @return UI\Form
	 */
	protected function createComponentSearchForm() {
		$form = new UI\Form;

		$form->getElementPrototype()->class = "form-horizontal";
		$ssidmacTxt = $form->addText('ssidmac', 'SSID/MAC:');
		$ssidmacTxt->getControlPrototype()->class='form-control';;

		$channelsQ = $this->wifiManager->getAllChannels();
		$channels = array();
		foreach($channelsQ as $ch) {
			$channels[$ch->channel] = $ch->channel;
		}

		$form->addSelect('channel','Kanál:', $channels)
			->setPrompt('Všechny kanály')
			->getControlPrototype()->addAttributes(array('class'=>'form-control'));

		$form->addSelect('security', 'Zabezpečení:', $this->wifiSecurityService->getAllWifiSecurityTypes(false))
			->setPrompt("Všechny typy")
			->getControlPrototype()->addAttributes(array('class'=>'form-control'));

		$sourcesfdb = $this->sourceManager->getAllSourcesAsKeyVal();
		$sources = array();
		foreach($sourcesfdb as $k=>$s) {
			$sources[$k] = ucfirst($s);
		}

		$form->addSelect('source', 'Zdroj:', $sources)
			->setPrompt("Všechny zdroje")
			->getControlPrototype()->addAttributes(array('class'=>'form-control'));

		$form->addSubmit('search', 'Vyhledat')->getControlPrototype()->addAttributes(array('class'=>'form-control btn btn-info btn-sm'));;

		return $form;
	}

}
