<?php
/**
 *
 * User: Roman
 * Date: 07.11.2015
 * Time: 11:22
 *
 * service class for wigle download
 * database changes etc.
 *
 */
namespace App\Service;


class WigleDownloadService extends BaseService {

    // vlozeni vsech mac z wifi do wigle_aps kde id_source je wigle - provest v transakci -> nejdrive tohle a pak delete z wifi
    // insert into wigle_aps(mac) select mac from wifi where id_source=2
    // delete from wifi where id_source=2
    // konec transakce


    public function moveFrom1TableToAnother($table1,$table2,$columns=array(),$conditions = array()) {


        $sql = "INSERT INTO $table2";
        if(!empty($columns)) $sql.="(";
        foreach($columns as $column) {
            $sql.=$column.",";
        }
        if(!empty($columns)) $sql = rtrim($sql,',') . ")";
        $sql.=" SELECT ";
        if(!empty($columns)) {
            foreach ($columns as $column) {
                $sql.=$column.',';
            }
            $sql = rtrim($sql,',');
        }
        else {
            $sql.="*";
        }
        $sql.=" FROM $table1";
        if(!empty($conditions)) {
            $sql.=' WHERE ';
            foreach($conditions as $cond=>$val) {
                $sql.=$cond . "=(" . $val . ")";
            }
        }



        $this->database->query($sql);


    }


    public function moveAllWigleWifiFromWifi2WigleAps() {
        $this->database->beginTransaction();

        $this->database->query("INSERT INTO wigle_aps(mac) SELECT mac FROM wifi WHERE id_source=?",WigleDownload::ID_SOURCE);
        $this->database->table("wifi")->where("id_source",WigleDownload::ID_SOURCE)->delete();

        $this->database->rollBack();
    }

    // TODO: presun wigle zaznamu z wifi do wigle_aps - staci MAC adresa + asi datum, zpracovano/nezpracovano a datum stazeni

    // TODO: wigle_download_queue u vsech zpracovanych sloupec download_catched nastavit na 0
    // TODO: projit vsechny download_requesty na wigle a pro kazdy najit zaznamy v wigle_download_queue ktere odpovidaji tomuto requestu
    // TODO: Pro každý download Request na Wigle, spočítat v tabulce Wigle_download_queue počet řádků a to nastavit danému řádku v download_request jako total_count
    // TODO: Downloaded_count u všech těch download requestu nastavit na 0







}