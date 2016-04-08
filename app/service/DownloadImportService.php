<?php
/**
 * User: Roman
 * Date: 19.12.2015
 * Time: 13:47
 */
namespace App\Service;


use App\Model\DownloadImport;

class DownloadImportService extends BaseService {

    const TABLE = "download_import";

    /**
     * @param DownloadImport $di
     * @return bool|int|\Nette\Database\Table\IRow
     */
    public function addImport(DownloadImport $di) {
        return $this->database->table(self::TABLE)->insert($di->toArray());
    }

    /**
     * @param DownloadImport $di
     */
    public function saveImport(DownloadImport $di) {
        if($di->getId()) {
            $this->database->table(self::TABLE)
                ->where('id',$di->getId())
                ->update($di->toArray());
        }
        else {
            $this->addImport($di);
        }
    }


    /**
     * @param int $state
     * @return DownloadImport[]
     */
    public function getDownloadImportsByState($state) {
        $disdb = $this->database->table(self::TABLE)->where('state',intval($state))->fetchAll();
        $dis = array();
        foreach($disdb as $didb) {
            $di = new DownloadImport();
            $di->setId($didb->id);
            $di->setIdWigleAps($didb->id_wigle_aps);
            $di->setMac($didb->mac);
            $di->setState($didb->state);
            $di->setIdGoogleRequest($didb->id_google_request);
            $dis[] = $di;
        }
        return $dis;
    }

}