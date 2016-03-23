<?php

namespace App\Presenters;

use App\Model\ArrayUtil;
use \App\Model\Coords;
use App\Model\DownloadImport;
use App\Model\MyUtils;
use App\Model\Wifi;
use \App\Service;
use Nette\Http\Url;
use Nette\Http\UrlScript;

class DownloadPresenter extends BasePresenter
{

    const FROM_TEMP_DIR_KEY = 'fromtempdir';

    /** @var \App\Service\WigleDownload @inject */
    public $wigleDownload;
    /** @var \App\Service\WifileaksDownload @inject */
    public $wifileaksDownload;
    /** @var Service\GoogleDownload @inject */
    public $googleDownload;
    /** @var \App\Service\DownloadRequest @inject */
    public $downloadRequest;
    /** @var \App\Service\WigleDownloadQueue @inject */
    public $downloadQueue;
    /** @var \App\Service\WifiManager @inject */
    public $wifiManager;
    /** @var Service\OptimizedWifiManager @inject */
    public $oWifiManager;
    /** @var Service\DownloadImportService @inject */
    public $downloadImportService;
    /** @var Service\NotifyEmailService @inject */
    public $notifyEmailService;

    /** maximalni pocet hodin, po ktery lze vytvaret pozadavek s vyfiltrovanymi body */
    const MAX_HOURS_FILTERED_REQUEST = 168;

    /**
     * CRON - 50times a day
     * process Wigle Download
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderWigle()
    {
        if ($this->wigleDownload) {
            $this->wigleDownload->downloadQueue = $this->downloadQueue;
            $this->wigleDownload->download();
        }
        $this->terminate();
    }

    /**
     * DOWNLOAD OBSERVATIONS OF MAC ADDRESS FROM WIGLE
     */
    public function renderWigleObservations()
    {
        if ($this->wigleDownload) {
            $this->wigleDownload->downloadObservations();
        }
        $this->terminate();
    }


    /**
     * process Wifileaks file parse and save to DB
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderWifileaks()
    {
        MyUtils::setIni(1800, '512M');
        $fromtempdir = $this->getHttpRequest()->getQuery(self::FROM_TEMP_DIR_KEY);
        if ($fromtempdir) {
            //TODO:
            // najit soubory v tempu
            // pokud nejaky odpovida regularu na nazev wifileaks souboru tak vzit (nejlepe ten nejnovejsi)
            $this->wifileaksDownload->download("../temp/wifileaks.tsv");
        } else {
            $this->wifileaksDownload->download();
        }


        $this->terminate();
    }

    /**
     * create google request -> save it to db
     */
    public function renderCreateGoogleRequest()
    {
        $this->googleDownload->setWifiManager($this->wifiManager);
        $req = $this->getHttpRequest();
        if ($req->getQuery("wid")) {
            $wifi = $this->wifiManager->getWifiById($req->getQuery("wid"));
            $this->googleDownload->createRequestFromWifi($wifi);
        }
        $this->terminate();
    }


    public function renderGoogle()
    {
        if (!$this->googleDownload) return;
        $this->googleDownload->download();
        $this->terminate();
    }


    public function renderPrepareWigleDownload($lat_start, $lat_end, $lon_start, $lon_end)
    {
        $coords = new Coords($lat_start, $lat_end, $lon_start, $lon_end);

        // ceska republika rozsah
        //$this->wigleDownload->generateLatLngDownloadArray(48.54570549184746,51.055207338584964,12.073974609375,18.8525390625);

        //hk rozsah
        //$this->wigleDownload->generateLatLngDownloadArray(new Coords(50.17074134967256,50.263887540074116,15.745468139648438,15.90545654296875));

        // $this->wigleDownload->generateLatLngDownloadArray($lat_start,$lat_end,$lon_start,$lon_end);
        $this->terminate();
    }

    /**
     * determine render action
     *
     * @param $show
     */
    public function actionAddWigleRequest($show)
    {
        $this->determineView($show);
    }

    public function renderAddWigleRequestHelp()
    {
    }

    public function renderAddWigleRequest()
    {
        $request = $this->getHttpRequest();
        $coords = new Coords($request->getQuery("lat1"), $request->getQuery("lat2"), $request->getQuery("lon1"), $request->getQuery("lon2"));
        $sourceDownloadFrom = (int)$request->getQuery("sourceDownloadFrom");
        $filter = $request->getQuery("filter");
        $filterSet = ArrayUtil::arrayHasSomeKey($filter, array("ssidmac", "channel", "source", "security", "ssid"));
        if($filterSet) {
            // request with filter set
            $state = $this->addFilteredRequest($coords,$filter,$sourceDownloadFrom);
        }
        else {
            // normal request to latitude longitude range
            $state = $this->addRequest(Service\WigleDownload::ID_SOURCE);
        }

        //$state = $this->addRequest(Service\WigleDownload::ID_SOURCE);
        $this->template->state = $state;
    }

    /**
     * vytvori pozadavek s nastavenym filtrem
     *
     * @param Coords $coords
     * @param array $filter
     * @param int $sourceDownloadFrom
     * @return string
     *
     */
    private function addFilteredRequest($coords,$filter,$sourceDownloadFrom) {
        $mode = WifiPresenter::MODE_ALL;

        if (isset($filter["mode"])) {
            $mode = $filter["mode"];
        }
        $params = $this->getParamsArray($coords,$mode,$filter);
        $nets = $this->oWifiManager->getNetsByParams($params, array('id,mac'));
        $macAddresses = array();
        foreach ($nets as $net) {
            $macAddresses[$net['mac']] = $net;
        }

        $notifyEmail = null;
        if($this->getHttpRequest()->getQuery("notifyEmail") != "") {
            $notifyEmail = $this->notifyEmailService->addNotifyEmail($this->getHttpRequest()->getQuery("notifyEmail"));
            setcookie("notify_email", $this->getHttpRequest()->getQuery("notifyEmail"), time()+3600);
        }


        if($sourceDownloadFrom == Service\WigleDownload::ID_SOURCE) {
            // jen z wigle -> pridame pouze do wigle_aps
            foreach(array_keys($macAddresses) as $macaddr) {
                $row = $this->wigleDownload->save2WigleAps(null,$macaddr,2);
                if($notifyEmail) $this->notifyEmailService->addNotifyEmailWigleAps($notifyEmail,$row->getPrimary(true));
            }
        }
        elseif($sourceDownloadFrom == Service\GoogleDownload::ID_SOURCE) {
            // z Wigle i Google -> pridame do wigle_aps a do download_import
            foreach(array_keys($macAddresses) as $macaddr) {
                // vytvoreni importu
                $downloadImport = new DownloadImport();
                $downloadImport->setMac($macaddr);

                // pridani do wigle fronty
                $row = $this->wigleDownload->save2WigleAps(null,$macaddr,2);

                // nastaveni importu
                $downloadImport->setIdWigleAps($row->getPrimary(true));
                if($notifyEmail) $this->notifyEmailService->addNotifyEmailWigleAps($notifyEmail,$row->getPrimary(true));
                $downloadImport->setState(DownloadImport::ADDED_WIGLE);

                // ulozeni importu
                $importId = $this->downloadImportService->addImport($downloadImport);
                if($notifyEmail) $this->notifyEmailService->addNotifyEmailDownloadImport($notifyEmail,$importId->getPrimary(true));
            }
        }
        return Service\DownloadRequest::STATE_SUCCESS_ADDED_TO_QUEUE;
    }


    /**
     * determine render action
     *
     * @param string $show
     */
    public function actionAddGoogleRequest($show)
    {
        $this->determineView($show);
    }

    /** show help for google request */
    public function renderAddGoogleRequestHelp()
    {
    }

    /** add download request */
    public function renderAddGoogleRequest()
    {
        /*$state = $this->addRequest(Service\GoogleDownload::ID_SOURCE);*/
        $request = $this->getHttpRequest();
        $coords = new Coords($request->getQuery("lat1"), $request->getQuery("lat2"), $request->getQuery("lon1"), $request->getQuery("lon2"));
        $sourceDownloadFrom = (int)$request->getQuery("sourceDownloadFrom");
        $filter = $request->getQuery("filter");
        $filterSet = ArrayUtil::arrayHasSomeKey($filter, array("ssidmac", "channel", "source", "security", "ssid"));
        if($filterSet) {
            // request with filter set
            $state = $this->addFilteredRequest($coords,$filter,$sourceDownloadFrom);
        }
        else {
            // normal request to latitude longitude range
            $this->downloadRequest->processDownloadRequestCreation(new Coords(
                $this->getHttpRequest()->getQuery("lat1"),
                $this->getHttpRequest()->getQuery("lat2"),
                $this->getHttpRequest()->getQuery("lon1"),
                $this->getHttpRequest()->getQuery("lon2")
            ), Service\GoogleDownload::ID_SOURCE);
        }
        $this->template->state = Service\DownloadRequest::STATE_SUCCESS_ADDED_TO_QUEUE;
    }


    /**
     * add download request
     *
     * @param int $idSource
     * @return bool|string
     */
    private function addRequest($idSource)
    {
        return $this->downloadRequest->processDownloadRequestCreation(new Coords(
            $this->getHttpRequest()->getQuery("lat1"),
            $this->getHttpRequest()->getQuery("lat2"),
            $this->getHttpRequest()->getQuery("lon1"),
            $this->getHttpRequest()->getQuery("lon2")
        ), $idSource);
    }


    /**
     * determines what view show
     */
    private function determineView($show)
    {
        if (strcasecmp($show, 'help') == 0) {
            $this->view = $this->getAction() . 'help';
        }
    }


    /**
     * CRON -> run every 1 HOUR (?)
     *
     * get one use created DownloadRequest, divide it by wifi density and add calculated records to wigle download queue
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderProcessWigleRequest()
    {
        MyUtils::setIni(1200, '256M');
        $req = $this->downloadRequest->getEldestDownloadRequest(Service\WigleDownload::ID_SOURCE);
        if ($req) {
            $coords = new Coords($req->lat_start, $req->lat_end, $req->lon_start, $req->lon_end);
            $this->downloadQueue->generateLatLngDownloadArray($coords, $req->id);
            $total_count = count($this->downloadQueue->getGeneratedCoords());
            $this->downloadQueue->save($req->id);
            $this->downloadRequest->setProcessed($req, $total_count);
        }
        $this->terminate();
    }

    /**
     * CRON - every 30 minutes
     *
     * get one user created DownloadRequest for google, and process it
     *
     * @throws \Nette\Application\AbortException
     */
    public function renderProcessGoogleRequest()
    {
        $this->googleDownload->setWifiManager($this->wifiManager);
        $req = $this->downloadRequest->getEldestDownloadRequest(Service\GoogleDownload::ID_SOURCE);
        $coords = new Coords($req->lat_start, $req->lat_end, $req->lon_start, $req->lon_end);
        $this->googleDownload->createRequestFromArea($coords);
        // TODO: nastavovat total_count na pocet zaznamu pridanych do google_request?
        $this->downloadRequest->setProcessed($req);
        $this->terminate();
    }

    public function actionAddRequests($data = '../temp/data.mac', $priority = 2, $fromWigle = true, $fromGoogle = false)
    {
    }

    public function renderTime2down($lat1, $lat2, $lon1, $lon2, $sourceDownloadFrom)
    {
        $coords = new Coords($lat1, $lat2, $lon1, $lon2);
        $sourceDownloadFrom = (int)$sourceDownloadFrom;
        $request = $this->getHttpRequest();
        $filter = $request->getQuery("filter");
        $filterSet = ArrayUtil::arrayHasSomeKey($filter, array("ssidmac", "channel", "source", "security", "ssid"));

        if ($filterSet) {
            $text = "<strong>Máte nastavený filtr, vytvoří se požadavek přímo na vyfiltrované body</strong><br />";
            $mode = WifiPresenter::MODE_ALL;

            if (isset($filter["mode"])) {
                $mode = $filter["mode"];
            }

            $params = $this->getParamsArray($coords,$mode,$filter);

            $nets = $this->oWifiManager->getNetsByParams($params, array('id,mac'));
            $netsCount = count($nets);
            $macAddresses = array();

            foreach ($nets as $net) {
                $macAddresses[$net['mac']] = $net;
            }

            $beforeMinutes = 0; $afterMinutes = 0;
            switch ($sourceDownloadFrom) {
                case Service\WigleDownload::ID_SOURCE:
                    $beforeMinutes = $this->wigleDownload->getWigleApsCount(2) * Service\BaseService::CRON_TIME_DOWNLOAD_WIGLE_OBSERVATIONS;
                    $afterMinutes = count($macAddresses) * Service\BaseService::CRON_TIME_DOWNLOAD_WIGLE_OBSERVATIONS;
                    break;
                case Service\GoogleDownload::ID_SOURCE:
                    $beforeMinutes = $this->wigleDownload->getWigleApsCount(2) * Service\BaseService::CRON_TIME_DOWNLOAD_WIGLE_OBSERVATIONS + $this->googleDownload->getGoogleRequestsCount(2) * Service\BaseService::CRON_TIME_DOWNLOAD_GOOGLE;
                    $afterMinutes = count($macAddresses) * Service\BaseService::CRON_TIME_DOWNLOAD_WIGLE_OBSERVATIONS + count($macAddresses) * Service\BaseService::CRON_TIME_DOWNLOAD_GOOGLE;
                    break;
            }

            $hodin1 = floor($beforeMinutes / 60); $minut1 = $beforeMinutes - $hodin1 * 60;
            $hodin2 = floor($afterMinutes / 60); $minut2 = $afterMinutes - ($hodin2 * 60);

            $celkemHodin = $hodin1 + $hodin2; $celkemMinut = $minut1 + $minut2;
            $celkemHodin += floor($celkemMinut / 60); $celkemMinut = $celkemMinut - floor($celkemMinut / 60) * 60;

            $text .= "Vyfiltrovaných sítí je " . $netsCount . ".<br />";

            if ($netsCount > 0) {
                $text .= "Získání dat bude trvat<br /><strong>";
                if ($hodin2 > 0) $text .= $hodin2 . "hodin";
                if ($hodin2 > 0 && $minut2 > 0) $text .= " a ";
                if ($minut2 > 0) $text .= $minut2 . "minut";
                if ($hodin1 > 0 || $minut1 > 0) $text .= "<br /> + ";
                if ($hodin1 > 0) $text .= $hodin1 . "hodin";
                if ($hodin1 > 0 && $minut1 > 0) $text .= " a ";
                if ($minut1 > 0) $text .= $minut1 . "minut";
                $text .= "</strong><br />celkem: <strong>";
                if ($celkemHodin > 0) $text .= ($celkemHodin) . " hodin";
                if ($celkemHodin > 0 && $celkemMinut > 0) $text .= " a ";
                if ($celkemMinut > 0) $text .= ($celkemMinut) . " minut";
                $text .= "</strong>";
            }
            if ($netsCount == 0) {
                echo "$('#createDownloadRequest').hide();";
            } else {
                echo "$('#createDownloadRequest').show();";
            }

            if ($celkemHodin > self::MAX_HOURS_FILTERED_REQUEST) {
                echo "$('#createDownloadRequest').hide();";
            }
            else {
                echo "$('#createDownloadRequest').show();";
            }
            $text .= '<br /><label for="notify_email">Až budou data získana informujte mě na email:</label><input type="email" name="notify_email" id="notify_email"'. ((isset($_COOKIE['notify_email']))?'value="'.$_COOKIE['notify_email'].'"':'') .' />';

            echo "$('#time2down').html('" . $text . "');";
        }
        $this->terminate();
    }


    public function renderNotifyEmail() {
        $notifyEmails = $this->notifyEmailService->getAllNotSentNotifyEmails();

        foreach($notifyEmails as $id=>$row) {
            $notDownloadedCount = $this->notifyEmailService->getNotDownloadedCountByNotifyEmailId($id);
            if($notDownloadedCount["pocet"] == 0) {
                // poslat email
                $this->notifyEmailService->notifyByEmail($id);
                $this->notifyEmailService->markAsSent($id);
            }
        }

        $this->terminate();

    }



}