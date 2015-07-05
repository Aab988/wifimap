<?php

namespace App\Presenters;

use \App\Model\WigleDownload, \App\Model\WifileaksDownload;

class DownloadPresenter extends BasePresenter {
    
    /**
     *
     * @var WigleDownload
     * @inject
     */
    public $wigleDownload;
    
    /**
     *
     * @var WifileaksDownload
     * @inject
     */
    public $wifileaksDownload;



	public function __construct(WifileaksDownload $wifileaksDownloadModel, WigleDownload $wigleDownloadModel) {
        $this->wigleDownload = $wigleDownloadModel;
        $this->wifileaksDownload = $wifileaksDownloadModel;
    }
    
    public function renderWigle() {
        $this->wigleDownload->download();
    }
    
    public function renderWifileaks() {
        $this->wifileaksDownload->download("../temp/wifileaks.tsv");
        
    }
    
}