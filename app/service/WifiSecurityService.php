<?php
/**
 * User: Roman
 * Date: 09.09.2015
 * Time: 12:59
 */
namespace App\Service;

use App\Model\WifiSecurity;

class WifiSecurityService extends BaseService {


    public function getById($id) {
        return $this->database->table("wifi_security")->where("id",$id)->fetch();
    }

    /**
     * @param bool $asObject
     * @return WifiSecurity[]
     */
    public function getAllWifiSecurityTypes($asObject = true) {
        $wstypes = array();
        $wss = $this->database->table("wifi_security")->fetchAll();
        foreach ($wss as $ws) {
            if($asObject) {
                $wstypes[] = new WifiSecurity($ws->id,$ws->label);
            }
            else {$wstypes[$ws->id] = $ws->label;}
        }
        return $wstypes;
    }

}