<?php
/**
 * User: Roman
 * Date: 15.09.2015
 * Time: 19:43
 */

namespace App\Service;


use App\Model\Coords;
use App\Model\Wifi;
use Nette\Utils\DateTime;

class GoogleDownload extends Download implements \IDownload {

    /** @var WifiManager */
    private $wifiManager;


    public function download()
    {
        $click_lat = 50.19069300754107;
        $click_lon = 15.804262161254883;

        $mapCoords = new Coords(50.18899429884056,50.19191362607472,15.797227464556954,15.808954082370064);

        $dlat = $mapCoords->getDeltaLat();
        $dlon = $mapCoords->getDeltaLon();

        $coords = new Coords(
            $click_lat-$dlat*0.03,
            $click_lat+$dlat*0.03,
            $click_lon-$dlon*0.03,
            $click_lon+$dlon*0.03
        );


        //$database = $this->wifileaksDownload->getDatabase();
        /*$nets = $this->wifiManager->getNetsRangeQuery($coords)->limit(2);
        $q = "";
        $ss = -80;
        foreach($nets as $net) {
            $mac = str_replace(":","-",$net->mac);
            $q.='&wifi=mac:'.$mac.'|ssid:'.$net->ssid.'|ss:'.$ss;
            $ss = -20;
        }
        dump($nets->fetchAll());
        $url = "https://maps.googleapis.com/maps/api/browserlocation/json?browser=firefox&sensor=true".$q;
       */

       /* echo $url;
        dump(json_decode(file_get_contents($url)));

            */

        // TODO: Implement download() method.
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
        $url.='&wifi=mac:'.$w1->getMac().'|ssid:'.$w1->getSsid().'|ss:'.$ss1;
        $url.='&wifi=mac:'.$w2->getMac().'|ssid:'.$w2->getSsid().'|ss:'.$ss2;
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
     */
    public function createRequestFromWifi(Wifi $wifi) {
        // zjistim jestli dana plocha je jiz stazena z wigle

        // ziskam druhou nejblizsi sit
        $w2 = $this->wifiManager->getClosestWifiToWifi($wifi);
        // pridam do DB fronty -> pokud tam jeste neni
        if($this->getGoogleRequestByWifiIds($wifi->getId(),$w2->getId())) {
            // log eistuje
            return;
        }

        $this->database->table("google_request")->insert(array(
            'created'=>new DateTime(),
            'id_wifi1' => $wifi->getId(),
            'id_wifi2' => $w2->getId(),
            'downloaded' => 'N'
        ));

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



    public function createRequestFromArea() {

    }


    /**
     * @param WifiManager $wifiManager
     */
    public function setWifiManager(WifiManager $wifiManager) {
        $this->wifiManager = $wifiManager;
    }


}