<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 15.12.2015
 * Time: 22:15
 */
namespace App\Presenters;
use App\Service\WifiManager;
use App\Service\WigleDownload;
use App\Model\MyUtils;

class ApiPresenter extends BasePresenter {

    /** @var  WigleDownload @inject */
    public $wigleDownload;
    /** @var WifiManager @inject */
    public $wifiManager;


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

        for($i = 0; $i <= $netsCount; $i+=1000) {
            $nets = $this->wifiManager->getNetsByParams($params,1000,$i);
            foreach($nets as $net) {
                fputcsv($file,$net->toArray(),';');
            }
        }

        fclose($file);

        // stazeni souboru
        $this->redirectUrl("../".$filename);
        $this->terminate();
    }



    public function actionAddRequests($data = '../temp/data.mac',$priority = 2,$fromWigle = true,$fromGoogle = false) {
        // TODO: rozparsovat data
        //$data = "00:00:00:00:00:00\nff:ff:ff:ff:ff:ff";
        $fh = fopen($data, 'r');
        $macAddresses = array(); $count = 0;
        while (!feof($fh)) {
            $mac = fgets($fh);
            if(MyUtils::isMacAddress($mac)) {
                $mac = MyUtils::macSeparator2Colon($mac);
                $macAddresses[] = $mac;
                $count++;
            }
        }
        fclose($fh);
        if($fromWigle) {
            $before = $this->wigleDownload->getWigleApsCount($priority);
            $this->wigleDownload->saveAll2WigleAps(null,$macAddresses,$priority);
            echo "bude trvat: " . ($count*30) . '+' . ($before*30) . 'minut';
            // pridat do wigle
        }
        if($fromGoogle) {
            // TODO: pridani k google stahovani
            // pridat do google
        }
        $this->terminate();
    }


}