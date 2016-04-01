<?php
/**
 * User: Roman
 * Date: 15.09.2015
 * Time: 19:43
 */

namespace App\Service;


use App\Model\Coords;
use App\Model\DownloadImport;
use App\Model\Log;
use App\Model\MyUtils;
use App\Model\Wifi;
use App\Model\WifiSecurity;
use Nette\Utils\DateTime;

class GoogleDownload extends Download implements IDownload {
    /** google source id in DB */
    const ID_SOURCE = 3;

    /** maximal accuracy in meters - accuracy >100 meters is bad */
    const MAX_ALLOWED_ACCURACY = 300;

    /** number of requests processed by one call of method download */
    const REQUESTS_LIMIT = 10;


    /** @var WifiManager */
    private $wifiManager;


    /**
     * main method, processed by cron
     */
    public function download()
    {
        $f = $this->database
            ->query("SELECT gr.id,w.id AS id1,w.mac AS mac1,w.ssid AS ssid1,w.altitude,w.sec,
                     w.comment,w.name,w.type,w.freenet,w.paynet,w.flags,w.wep,w.channel,w.bcninterval,
                     w.qos,w2.id AS id2,w2.mac AS mac2,w2.ssid AS ssid2
                     FROM google_request gr
                     JOIN wifi w ON (gr.id_wifi1 = w.id)
                     JOIN wifi w2 ON (gr.id_wifi2 = w2.id)
                     WHERE gr.downloaded='N'
                     ORDER BY gr.priority DESC
                     LIMIT ".self::REQUESTS_LIMIT." FOR UPDATE")->fetchAll();

        $wifis = array();

        foreach($f as $ws) {
            $w1 = new Wifi();
            $w1->setMac($ws->mac1);
            $w1->setSsid($ws->ssid1);
            $w1->setAltitude($ws->altitude);
            $w1->setSec($ws->sec);
            $w1->setComment($ws->comment);
            $w1->setName($ws->name);
            $w1->setType($ws->type);
            $w1->setFreenet($ws->freenet);
            $w1->setPaynet($ws->paynet);
            $w1->setFlags($ws->flags);
            $w1->setWep($ws->wep);
            $w1->setChannel($ws->channel);
            $w1->setBcninterval($ws->bcninterval);
            $w1->setQos($ws->qos);
            $w2 = new Wifi();
            $w2->setMac($ws->mac2);
            $w2->setSsid($ws->ssid2);

            $url = $this->generateGoogleRequestUrl($w1,$w2);
            // vraci accuracy -> v metrech
            // location -> lat,lng
            // a status
            $data = $this->getDataFromGoogle($url);
            dump($data);
            if($data->status == 'OK') {
                if($data->accuracy < self::MAX_ALLOWED_ACCURACY) {
                    $w = new Wifi();
                    // naplnit hodnoty podle predchozi w1 kromě id,id_source,latitude,longitude a accuracy
                    $w->setMac(MyUtils::macSeparator2Colon($w1->getMac()));
                    $w->setSsid($w1->getSsid());
                    $w->setAltitude($w1->getAltitude());
                    $w->setSec($w1->getSec());
                    $w->setComment($w1->getComment());
                    $w->setName($w1->getName());
                    $w->setType($w1->getType());
                    $w->setFreenet($w1->getFreenet());
                    $w->setPaynet($w1->getPaynet());
                    $w->setFlags($w1->getFlags());
                    $w->setWep($w1->getWep());
                    $w->setChannel($w1->getChannel());
                    $w->setBcninterval($w1->getBcninterval());
                    $w->setQos($w1->getQos());
                    // naplnit id source,latitude,longitude,accuracy
                    $w->setAccuracy($data->accuracy);
                    $w->setLatitude($data->location->lat);
                    $w->setLongitude($data->location->lng);
                    $w->setSource(self::ID_SOURCE);
                    $w->setDateAdded(new DateTime());
                    // ulozit
                    $wifis[] = $w;
                }
                else {
                    // neukladat -> zalogovat
                    $this->logger->addLog(new Log(Log::TYPE_WARNING,'GOOGLE DOWNLOAD','Moc mala presnost: ' . $data->accuracy.'/nURL='.$url));
                }
                $this->database->table("google_request")
                    ->where("id",$ws->id)
                    ->update(array("downloaded"=>'Y'));
                // nastavit priznak staezno na download
                $this->database->table(DownloadImportService::TABLE)
                    ->where('id_wigle_aps',$ws->id1)
                    ->where('state',DownloadImport::ADDED_GOOGLE)
                    ->update(array('state'=>DownloadImport::DOWNLOADED_GOOGLE));

            }
        }
        foreach($wifis as $wifi) {
            $this->save($wifi);
        }
    }


    /**
     * creates google request URL from 2 Wi-Fi
     *
     * @param Wifi $w1 wifi, that i want acquire
     * @param Wifi $w2 second wifi, close to first one -> it's because i cant ask google for info only with one wifi
     * @return string
     */
    private function generateGoogleRequestUrl(Wifi $w1, Wifi $w2) {
        // base url
        $url = "https://maps.googleapis.com/maps/api/browserlocation/json?browser=firefox&sensor=true";
        // random signal strength
        $ss1 = rand(-85,-75);
        $ss2 = rand(-25,-20);
        // mac address in XX-XX-XX-XX-XX-XX format
        $w1->setMac(str_replace(":","-",$w1->getMac()));
        $w2->setMac(str_replace(":","-",$w2->getMac()));
        // add to url
        $url.='&wifi=mac:'.$w1->getMac().'|ssid:'.urlencode($w1->getSsid()).'|ss:'.$ss1;
        $url.='&wifi=mac:'.$w2->getMac().'|ssid:'.urlencode($w2->getSsid()).'|ss:'.$ss2;
        return $url;
    }

    /**
     * return array with information from google
     *
     * @param $url google download url
     * @return array
     */
    private function getDataFromGoogle($url) {
        return json_decode(file_get_contents($url));
    }



    /**
     * creates google request from one net (requested by InfoWindow button)
     *
     * @param Wifi $wifi
     * @param int $priority
     *
     */
    public function createRequestFromWifi(Wifi $wifi, $priority = 1) {
        $this->wifiManager = new WifiManager($this->database);

        // ziskam druhou nejblizsi sit
        $w2 = $this->wifiManager->getClosestWifiToWifi($wifi);

        if($w2) {
            // pridam do DB fronty -> pokud tam jeste neni
            if($this->getGoogleRequestByWifiIds($wifi->getId(),$w2->getId())) {
                // log eistuje
                return;
            }

            $id = $this->database->table("google_request")->insert(array(
                'created'=>new DateTime(),
                'id_wifi1' => $wifi->getId(),
                'id_wifi2' => $w2->getId(),
                'downloaded' => 'N',
                'priority' => $priority
            ));
            return $id->getPrimary(true);
        }
        return null;
    }


    public function createRequestFromArea(Coords $coords) {
        $wfs = $this->wifiManager->getAllNetsInLatLngRange($coords);

        foreach($wfs as $w) {
            $this->createRequestFromWifi($w);
        }

        // vratit chybu/uspech

    }



    private function getGoogleRequestByWifiIds($w1id,$w2id) {
        return $this->database->table("google_request")
            ->select("datediff(now(),created) AS diff")
            ->where("id_wifi1",$w1id)
            ->where("id_wifi2",$w2id)
            ->where("(downloaded='N' OR (downloaded='Y' AND datediff(now(),`created`) < 7))")
            ->order("diff ASC")
            ->fetch();
    }



    /**
     * @param WifiManager $wifiManager
     */
    public function setWifiManager(WifiManager $wifiManager) {
        $this->wifiManager = $wifiManager;
    }

    /**
     * @param int $priority
     * @return int
     */
    public function getGoogleRequestsCount($priority = 2) {
        $count = $this->database->table('google_request')
            ->where('downloaded','N');
        if($priority) $count->where('priority >= ?',$priority);
        return $count->count();
    }


}