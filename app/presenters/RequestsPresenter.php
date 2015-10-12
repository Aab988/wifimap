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
        $wrs = $this->downloadRequest->getAllRequestsByIdSource(WigleDownload::ID_SOURCE);
        $this->template->wigleRequests = $wrs;
        $wrfm = array();
        foreach($wrs as $wr) {
            $wrfm[$wr->lat_start.$wr->lat_end.$wr->lon_start.$wr->lon_end] = $wr;
        }


        $this->template->wigleRequestsMap = $wrfm;
        $this->template->googleRequests = $this->downloadRequest->getAllRequestsByIdSource(GoogleDownload::ID_SOURCE);

    }


}