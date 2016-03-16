<?php
namespace App\Service;
use App\Model\Log;
use App\Model\Wifi;
use Nette;

/**
 *
 * Save Wifis into DB
 *
 * Class Download
 * @package App\Model
 */
class Download extends BaseService {


    /**
     * save one Wi-fi network into DB
     * @param Wifi $wifi
     * @return Nette\Database\IRow
     */
    protected function saveSimpleInsert(Wifi $wifi) {
        try {
            $row = $this->database->table('wifi')->insert($this->prepareArrayForDB($wifi));
            return $row;
        }
        catch(\PDOException $e) {
            $this->logger->addLog(new Log(Log::TYPE_ERROR,'WIFI INSERT', 'nepodarilo se ulozit bod do tabulky wifi, zprava:'.$e->getMessage()));
            return null;
        }
    }

    /**
     * save whole array of Wi-Fi networks
     * @param Wifi[] $wifis
     * @return Nette\Database\Table\IRow[]
     */
    protected function saveAll(array $wifis) {
        $insertedRows = array();
        foreach($wifis as $wifi) {
            $insertedRows[] = $this->saveSimpleInsert($wifi);
        }
        return $insertedRows;
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
                    $this->database->table('wifi')->insert($data);
                }
                catch(\PDOException $pdoe) {
                    echo $pdoe->getMessage();
                }
                $data = array();
            }
        }
        if(count($data)) {
            // TODO: osetrit ze uz existuje (DUPLIKATNI KLIC)
            $this->database->table('wifi')->insert($data);
        }
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
            "sec" => $wifi->getSec(),
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
            "qos" => $wifi->getQos(),
            "accuracy"=>$wifi->getAccuracy(),
            "calculated"=>$wifi->getCalculated()
        );
        return $array;
    }

    /**
     * only save without preparation (already have right sec)
     *
     * @param Wifi $w
     */
    public function save(Wifi $w) {
        $this->database->table('wifi')->insert($w->toArray());
    }

}