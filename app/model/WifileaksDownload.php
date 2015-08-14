<?php
namespace App\Model;

class WifileaksDownload extends Download implements \IDownload {

    /**
     * Wifileaks ID from DB
     */
    const ID_SOURCE = 1;


    /**
     * main method - parse whole file and save into DB
     * @param string $file_name
     */
    public function download($file_name = "") {
        if($file_name != "") {
            $data = $this->parseData($file_name);
            $this->saveMultiInsert($data, 1000);
        }
    }

    /**
     * parse one line and create Wifi object
     * @param string $line
     * @return Wifi
     */
    private function parseLine($line) {
        $wifi = new Wifi();
        $array = explode("\t", $line);
        $wifi->setMac($array[0]);
        $wifi->setSsid($array[1]);
        $wifi->setSec($array[2]);
        $wifi->setLatitude(doubleval($array[3]));
        $wifi->setLongitude(doubleval($array[4]));
        $wifi->setAltitude(doubleval($array[5]));
        $wifi->setSource(self::ID_SOURCE);
        return $wifi;
    }


    /**
     * parse whole file
     * @param string $fileName
     * @return Wifi[]
     */
    private function parseData($fileName) {
        $fh = fopen($fileName, 'r');

        $wifis = array();
        while (!feof($fh)) {
            $line = fgets($fh);
            if ($line[0] != "*") {
                $wifi = $this->parseLine($line);
                $wifis[] = $wifi;
            }
        }
        fclose($fh);
        return $wifis;
    }
}

