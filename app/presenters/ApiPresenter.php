<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 15.12.2015
 * Time: 22:15
 */
namespace App\Presenters;
use App\Model\Coords;
use App\Model\DownloadImport;
use App\Service\DownloadImportService;
use App\Service\SourceManager;
use App\Service\WifiManager;
use App\Service\WifiSecurityService;
use App\Service\WigleDownload;
use App\Model\MyUtils;

class ApiPresenter extends BasePresenter {

    /** @var  WigleDownload @inject */
    public $wigleDownload;
    /** @var WifiManager @inject */
    public $wifiManager;
    /** @var DownloadImportService @inject */
    public $downloadImportService;
    /** @var SourceManager @inject */
    public $sourceManager;
    /** @var WifiSecurityService @inject */
    public $wifiSecurityService;


    public function actionDownload() {
        $params = array();

        $parameters = $this->request->getParameters();

        foreach($parameters as $k=>$p) {
            switch ($k) {
                case 'ssidmac':
                    if(MyUtils::isMacAddress($p)) {$params['mac'] = $p;}
                    else {$params['ssid'] = $p;}
                    break;
                case 'channel':
                    if($p!=null && $p!='') $params['channel'] = intval($p);
                    break;
                case 'security':
                    if($p!=null && $p!='') $params['sec'] = intval($p);
                    break;
                case 'source':
                    if($p!=null && $p!='') $params['id_source'] = intval($p);
                    break;
            }
        }

        $netsCount = $this->wifiManager->getNetsCountByParams($params);

        // FILENAME = DATETIME_SSID|MAC_CHANNEL_SECURITY_SOURCE
        // vygenerovat nazev podle parametru
        $filename = date('YmdHis');
        foreach($params as $k=>$v) $filename.='_' . $v;
        $filename = '../temp/' . $filename .'.csv';

        $file = fopen($filename,"w");

        $array = array('Zdroj','Datum pridani', 'MAC', 'SSID', 'latitude', 'longitude', 'altitude',
            'zabezpeceni', 'kanal', 'presnost', 'wigle komentar', 'wigle nazev', 'typ', 'wigle poprve',
            'wigle naposledy', 'flags', 'bcninterval');

        fputcsv($file,$array,';');

        $sources = $this->sourceManager->getAllSourcesAsKeyVal();
        $securities = $this->wifiSecurityService->getAllWifiSecurityTypes(false);

        for($i = 0; $i <= $netsCount; $i+=1000) {
            $nets = $this->wifiManager->getNetsByParams($params,1000,$i);

            foreach($nets as $net) {
                $array = array(
                    'zdroj' => $sources[$net->id_source],
                    'pridano' => $net->date_added,
                    'mac' => $net->mac,
                    'ssid' => $net->ssid,
                    'latitude' => $net->latitude,
                    'longitude' => $net->longitude,
                    'altitude' => $net->altitude,
                    'zabezpeceni' => $securities[$net->sec],
                    'kanal' => $net->channel,
                    'presnost' => $net->accuracy,
                    'comment' => $net->comment,
                    'name' => $net->name,
                    'type' => $net->type,
                    'firsttime' => $net->firsttime,
                    'lasttime' => $net->lasttime,
                    'flags' => $net->flags,
                    'bcninterval' => $net->bcninterval
                );
                fputcsv($file,$array,';');
            }
        }

        fclose($file);

        // stazeni souboru
        $this->payload->file= $filename;
        $this->sendPayload();

        //$this->redirectUrl("../".$filename);
        $this->terminate();
    }



    public function actionAddRequests($data = '../temp/data.mac',$priority = 2) {
        // rozparsovat data
        if(!file_exists($data)) {
            echo "soubor nenalezen";
            $this->terminate();
        }

        $fh = fopen($data, 'r');
        $macAddresses = array(); $count = 0;
        while (!feof($fh)) {
            $mac = fgets($fh);
            if(MyUtils::isMacAddress($mac)) {
                $mac = MyUtils::macSeparator2Colon($mac);
                $macAddresses[] = trim($mac);
                $count++;
            }
        }
        fclose($fh);

        $before = $this->wigleDownload->getWigleApsCount($priority);
        foreach($macAddresses as $macaddr) {
            // vytvoreni importu
            $downloadImport = new DownloadImport();
            $downloadImport->setMac($macaddr);

            // pridani do wigle fronty
            $row = $this->wigleDownload->save2WigleAps(null,$macaddr,$priority);

            // nastaveni importu
            $downloadImport->setIdWigleAps($row->getPrimary());
            $downloadImport->setState(DownloadImport::ADDED_WIGLE);

            // ulozeni importu
            dump($downloadImport);
            $this->downloadImportService->addImport($downloadImport);
        }
        echo "bude trvat: " . ($count*30) . '+' . ($before*30) . 'minut';

        $this->terminate();
    }


    public function actionAdd2GoogleRequests() {
        $dis = $this->downloadImportService->getDownloadImportsByState(DownloadImport::DOWNLOADED_WIGLE);
        foreach($dis as $di) {
            $nets = $this->wifiManager->getNetsByParams(array('mac'=>$di->getMac()));
            dump($nets);

        }

        $this->terminate();
    }



    public function renderAccuracy() {
        if($this->getHttpRequest()->getQuery("mac") != "" && $this->getHttpRequest()->getQuery("r_latitude") != "" && $this->getHttpRequest()->getQuery("r_longitude") != "" && MyUtils::isMacAddress($this->getHttpRequest()->getQuery("mac"))) {

            $mac = MyUtils::macSeparator2Colon($this->getHttpRequest()->getQuery("mac"));
            $tableData = $this->wifiManager->getDistanceFromOriginalGPS($mac,doubleval($this->getHttpRequest()->getQuery("r_latitude")), doubleval($this->getHttpRequest()->getQuery("r_longitude")));

            $data = array();
            foreach($tableData as $td) {
                $coords = new Coords($td["latitude"],$this->getHttpRequest()->getQuery("r_latitude"),$td["longitude"],$this->getHttpRequest()->getQuery("r_longitude"));
                $inM = $coords->getDistanceInMetres();
                $arr = $td;
                $arr["inM"] = $inM;
                $data[] = $arr;
            }

            $this->template->table = $data;

        }


    }



}