<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 12.10.2015
 * Time: 17:11
 */
namespace App\Presenters;
use App\Service\DownloadRequest;
use App\Service\GoogleDownload;
use App\Service\WigleDownload;

class RequestsPresenter extends BasePresenter {

    /** @var DownloadRequest @inject */
    public $downloadRequest;

    public function renderDefault() {
        $this->template->wigleRequests = $this->downloadRequest->getAllRequestsByIdSource(WigleDownload::ID_SOURCE);
        $this->template->googleRequests = $this->downloadRequest->getAllRequestsByIdSource(GoogleDownload::ID_SOURCE);

    }


}