<?php
namespace App\Model;

class WifileaksDownload extends Download implements \IDownload {

    /**
     * Wifileaks ID from DB
     */
    const ID_SOURCE = 1;

    /**
     * how many nets will be saved simultaneously
     */
    const MULTIINSERT_ROWS = 1000;


    /**
     * main method - parse whole file and save into DB
     * @param string $file_name
     */
    public function download($file_name = "") {
        if($file_name != "") {
            try {
                $data = $this->parseData($file_name);
                // TODO: logovat bylo vlozeno $data zaznamu z wifileaks
            }
            catch(\Exception $e) {
                echo $e->getMessage();
            }

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
     * parse whole file -> return how many nets were parsed
     * @uses WifileaksDownload::MULTIINSERT_ROWS as multiinsert rows count
     * @param string $fileName
     * @return int
     */
    private function parseData($fileName) {
        $fh = fopen($fileName, 'r');

        $wifis = array(); $count = 0;
        while (!feof($fh)) {
            $line = fgets($fh);
            if ($line[0] != "*") {
                $count++;
                $wifi = $this->parseLine($line);
                $wifis[] = $wifi;
            }
            if(count($wifis) == self::MULTIINSERT_ROWS) {
                $this->saveMultiInsert($wifis,self::MULTIINSERT_ROWS);
                $wifis = array();
            }
        }
        $this->saveMultiInsert($wifis,self::MULTIINSERT_ROWS);
        fclose($fh);
        return $count;
    }
}

