<?php
/**
 * User: Roman
 * Date: 23.08.2015
 * Time: 21:12
 */
namespace App\Model;
use Nette;

class DownloadQueue extends Nette\Object {

    /** @var Nette\Database\Context */
    private $database;

    /**
     * return one random not downloaded record from queue
     *
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    public function getRandomNotDownloadedRecord() {
        return $this->database->table("download_queue")->select("id,lat_start,lat_end,lon_start,lon_end,from,to")
            ->where("downloaded = ?", 0)
            ->order("rand()")
            ->limit(1)
            ->fetch();
    }

    /**
     * add one record to download queue
     *
     * @param Coords $coords
     * @param int $id_source
     * @uses WigleDownload::ID_SOURCE as default value for $id_source param
     * @param int $from
     *
     */
    public function addRecord($coords,$id_source = WigleDownload::ID_SOURCE, $from = 0) {
        $data = array(
            "id_source" => $id_source,
            "lat_start" => $coords->getLatStart(),
            "lat_end" => $coords->getLatEnd(),
            "lon_start" => $coords->getLonStart(),
            "lon_end" => $coords->getLonEnd(),
            "downloaded" => 0,
        );
        if($from != 0) {
            $data["from"] = $from;
            $data["to"] = $from + 99;
        }
        $this->database->query("insert into download_queue",$data);
    }




    /**
     * @param Nette\Database\Context $database
     */
    public function setDatabase($database) {
        $this->database = $database;
    }



}