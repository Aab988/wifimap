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
        // presun wigle zaznamu z wifi do wigle_aps - staci MAC adresa + asi datum, zpracovano/nezpracovano a datum stazeni
        $this->database->query("INSERT INTO wigle_aps(mac) SELECT mac FROM wifi WHERE id_source=?",WigleDownload::ID_SOURCE);
        $this->database->table("wifi")->where("id_source",WigleDownload::ID_SOURCE)->delete();

        // wigle_download_queue u vsech zpracovanych sloupec count_downloaded_observations nastavit na 0
        $this->database->table('wigle_download_queue')->update(array('count_downloaded_observations'=>0));

        //  projit vsechny download_requesty na wigle a pro kazdy najit zaznamy v wigle_download_queue ktere odpovidaji tomuto requestu
        //  Pro každý download Request na Wigle, spočítat v tabulce Wigle_download_queue počet řádků a to nastavit danému řádku v download_request jako total_count
        $drs = $this->database->table('download_request')->where('id_source',WigleDownload::ID_SOURCE)->fetchAll();
        foreach($drs as $dr) {
            // najit podle lat_start,lat_end,lon_start,lon_end zaznamy v tabulce wigle_download_queue a priradit je
            // -> jejich pocet je total_count
            $rows = $this->database->table('wigle_download_queue')
                ->where('lat_start BETWEEN ? AND ?',$dr->lat_start,$dr->lat_end)
                ->where('lat_end BETWEEN ? AND ?',$dr->lat_start,$dr->lat_end)
                ->where('lon_start BETWEEN ? AND ?',$dr->lon_start,$dr->lon_end)
                ->where('lon_end BETWEEN ? AND ?',$dr->lon_start,$dr->lon_end);
            $total = $rows->count();
            $rows->update(array('id_download_request'=>$dr->id));

            // -> downloaded_count u vsech nastavit na 0
            $dr->update(array('total_count'=>$total,'downloaded_count'=>0));
        }
        $this->database->commit();
    }


}