<?php

namespace App\Model;

use Nette;

/**
 *
 * Zajistuje praci s databazi (vkladani zaznamu)
 *
 * Class Download
 * @package App\Model
 */
class Download extends Nette\Object {
    protected $database;
    
     
    
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * ulozi jednu Wifi sit
     * @param Wifi $wifi sit
     */
    protected function saveSimpleInsert(Wifi $wifi) {
        try {
            $this->database->query("insert into wifi", $this->prepareArrayForDB($wifi));
        }
        catch(\PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * ulozi cele pole Wifi siti
     * @param array $wifis pole Wifi objektu
     */
    protected function saveAll(array $wifis) {
        foreach($wifis as $wifi) {
            $this->saveSimpleInsert($wifi);
        }
    }

    /**
     * vlozi do DB zaznamy multiinsertem
     * @param array $wifis pole Wifi objektu
     * @param $howmany int pocet kolik objektu ukladat zaroven v multiinsertu
     */
    protected function saveMultiInsert(array $wifis, $howmany) {
        $data = array();
        foreach($wifis as $w) {
            $data[] = $this->prepareArrayForDB($w);
            if(count($data) == $howmany) {
                $this->database->query("insert into wifi", $data);
                $data = array();
            }
        }
        $this->database->query("insert into wifi", $data);
    }

    /**
     * vytvori z objektu pole pro vlozeni do DB
     * @param Wifi $wifi jedna wifi sit
     * @return array pole pro vlozeni do db
     */
    private function prepareArrayForDB(Wifi $wifi) {
        $array = array(
            "id_source" => $wifi->getSource(),
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
            "qos" => $wifi->getQos()
        );
        return $array;
    }

}