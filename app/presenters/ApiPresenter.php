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
use App\Model\Log;
use App\Service\DownloadImportService;
use App\Service\GoogleDownload;
use App\Service\SourceManager;
use App\Service\WifileaksDownload;
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


    /**
     * export to CSV by filter
     *
     * @throws \Nette\Application\AbortException
     */
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
        $this->terminate();
    }

    /**
     * @param string $data
     * @param int    $priority
     * @throws \Nette\Application\AbortException
     */
    public function actionAddRequests($data = '../temp/data.mac',$priority = 2) {

        // parse data
        if(!file_exists($data)) {
            $this->logger->addLog(new Log(Log::TYPE_ERROR,"import","file " . $data . " not found"));
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

            // create import
            $downloadImport = new DownloadImport();
            $downloadImport->setMac($macaddr);

            // add to wigle queue
            $row = $this->wigleDownload->save2WigleAps(null,$macaddr,$priority);

            // set import state
            $downloadImport->setIdWigleAps($row->getPrimary());
            $downloadImport->setState(DownloadImport::ADDED_WIGLE);

            // save import
            $this->downloadImportService->addImport($downloadImport);
        }
        echo "bude trvat: " . ($count*30) . '+' . ($before*30) . 'minut';

        $this->terminate();
    }

    /**
     * accuracy page
     */
    public function renderAccuracy() {
        if($this->getHttpRequest()->getQuery("mac") != "" && $this->getHttpRequest()->getQuery("r_latitude") != ""
            && $this->getHttpRequest()->getQuery("r_longitude") != "" && MyUtils::isMacAddress($this->getHttpRequest()->getQuery("mac"))) {

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
            usort($data,"self::Sort");

            $chWigleMin = PHP_INT_MAX; $chWigleMax = 0; $chWigleTotal = 0;
            $chGoogleMin = PHP_INT_MAX; $chGoogleMax = 0; $chGoogleTotal = 0;
            $chWigleAvg = 0; $wigleCount = 0;
            $wifileaksCount = 0; $wifileaksTotal = 0;
            $googleCount = 0;

            foreach($data as $d) {
                if($d["id_source"] == WifileaksDownload::ID_SOURCE) {
                    $wifileaksCount++;
                    $wifileaksTotal += $d["inM"];
                }
                if($d["id_source"] == WigleDownload::ID_SOURCE) {
                    $wigleCount++;
                    if($chWigleMin > $d["inM"]) $chWigleMin = $d["inM"];
                    if($chWigleMax < $d["inM"]) $chWigleMax = $d["inM"];
                    $chWigleTotal += $d["inM"];
                    if($d["calculated"] == 1) $chWigleAvg = $d["inM"];
                }
                if($d["id_source"] == GoogleDownload::ID_SOURCE) {
                    $googleCount++;
                    if($chGoogleMin > $d["inM"]) $chGoogleMin = $d["inM"];
                    if($chGoogleMax < $d["inM"]) $chGoogleMax = $d["inM"];
                    $chGoogleTotal += $d["inM"];
                }
            }

            if($chWigleAvg == 0) $chWigleAvg = $chWigleTotal / $wigleCount;
            $chGoogleAvg = $chGoogleTotal / $googleCount;
            $chWifileaks = $wifileaksTotal / $wifileaksCount;

            $this->template->chWifileaks = $chWifileaks;
            $this->template->chWigleMin = $chWigleMin;
            $this->template->chWigleMax = $chWigleMax;
            $this->template->chWigleAvg = $chWigleAvg;
            $this->template->chGoogleMin = $chGoogleMin;
            $this->template->chGoogleMax = $chGoogleMax;
            $this->template->chGoogleAvg = $chGoogleAvg;

            $this->template->table = $data;
        }
    }

    /**
     * sort by distance in meters
     * @param $a
     * @param $b
     * @return int
     */
    public static function Sort($a,$b) {
        if ($a["inM"] == $b["inM"]) {
            return 0;
        }
        return ($a["inM"] < $b["inM"]) ? -1 : 1;
    }

}