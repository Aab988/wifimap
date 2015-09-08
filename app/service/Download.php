<?php
namespace App\Service;
use App\Model\Wifi;
use Nette;

/**
 *
 * Save Wifis into DB
 *
 * Class Download
 * @package App\Model
 */
class Download extends Nette\Object {

    /**
     * @var Nette\Database\Context
     */
    protected $database;

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * save one Wi-fi network into DB
     * @param Wifi $wifi
     */
    protected function saveSimpleInsert(Wifi $wifi) {
        try {
            $this->database->query("insert into wifi", $this->prepareArrayForDB($wifi));
        }
        catch(\PDOException $e) {
            // TODO: LOG ERROR
            //echo $e->getMessage();
        }
    }

    /**
     * save whole array of Wi-Fi networks
     * @param Wifi[] $wifis
     */
    protected function saveAll(array $wifis) {
        foreach($wifis as $wifi) {
            $this->saveSimpleInsert($wifi);
        }
    }

    /**
     * save whole array into DB using multiinsert query
     *
     * @param Wifi[] $wifis
     * @param $howmany int how many rows insert at once
     */
    protected function saveMultiInsert(array $wifis, $howmany) {
        $data = array();
        foreach($wifis as $w) {
            $data[] = $this->prepareArrayForDB($w);
            if(count($data) == $howmany) {
                try {
                    $this->database->query("insert into wifi", $data);
                }
                catch(\PDOException $pdoe) {
                    echo $pdoe->getMessage();
                }
                $data = array();
            }
        }
        $this->database->query("insert into wifi", $data);
    }

    /**
     * change Wi-Fi object to associative array
     *
     * @param Wifi $wifi
     * @return array
     */
    private function prepareArrayForDB(Wifi $wifi) {
        $wifi->synchronizeSecurity();
        $array = array(
            "id_source" => $wifi->getSource(),
            "date_added" => date("Y-m-d"),
            "mac" => $wifi->getMac(),
            "ssid" => $wifi->getSsid(),
            "sec" => $wifi->getSec()+1,
            "latitude" => $wifi->getLatitude(),
            "longitude" => $wifi->getLongitude(),
            "altitude" => $wifi->getAltitude(),
            "comment" => $wifi->getComment(),
            "name" => $wifi->getName(),
            "type" => $wifi->getType(),
            "freenet" => $wifi->getFreenet(),
            "paynet" => $wifi->getPaynet(),
            "firsttime" => $wifi->getFirsttime(),
            "lasttime" => $wifi->getLasttime(),
            "flags" => $wifi->getFlags(),
            "wep" => $wifi->getWep(),
            "lastupdt" => $wifi->getLastupdt(),
            "channel" => $wifi->getChannel(),
            "bcninterval" => $wifi->getBcninterval(),
            "qos" => $wifi->getQos()
        );
        return $array;
    }

    /**
     * @return Nette\Database\Context
     */
    public function getDatabase() {
        return $this->database;
    }
}