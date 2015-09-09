<?php
/**
 * User: Roman
 * Date: 09.09.2015
 * Time: 12:59
 */
namespace App\Service;

use App\Model\WifiSecurity;

class WifiSecurityService extends BaseService {


    /**
     * @return WifiSecurity[]
     */
    public function getAllWifiSecurityTypes() {
        $wstypes = array();
        $wss = $this->database->table("wifi_security")->fetchAll();
        foreach ($wss as $ws) {
            $wstypes[] = new WifiSecurity($ws->id,$ws->label);
        }
        return $wstypes;
    }

}