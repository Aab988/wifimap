<?php
/**
 * User: Roman
 * Date: 09.09.2015
 * Time: 12:59
 */
namespace App\Service;

use App\Model\WifiSecurity;

class WifiSecurityService extends BaseService {

    const TABLE = "wifi_security";

    /**
     * @param int $id
     * @return WifiSecurity
     */
    public function getById($id) {
        $ws = new WifiSecurity();
        $row = $this->database->table(self::TABLE)->where("id",$id)->fetch();
        if($row) {
            $ws = new WifiSecurity($row->id,$row->label);
        }
        return $ws;
    }

    /**
     * @param bool $asObject
     * @return WifiSecurity[]
     */
    public function getAllWifiSecurityTypes($asObject = true) {
        $wstypes = array();
        $wss = $this->database->table(self::TABLE)->fetchAll();
        foreach ($wss as $ws) {
            if($asObject) {
                $wstypes[] = new WifiSecurity($ws->id,$ws->label);
            }
            else { $wstypes[$ws->id] = $ws->label; }
        }
        return $wstypes;
    }

}