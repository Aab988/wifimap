<?php
namespace App\Service;
use App\Model\Wifi;

class WifileaksDownload extends Download implements \IDownload {

    /**
     * Wifileaks ID from DB
     */
    const ID_SOURCE = 1;

    /**
     * how many nets will be saved simultaneously
     */
    const MULTIINSERT_ROWS = 1000;

    /** wifileaks download file location */
    const WIFILEAKS_DOWNLOAD_DIR = "http://download.wifileaks.cz/data/";

    /** wifileaks download file name regular expression */
    const WIFILEAKS_DOWNLOAD_FILENAME_PATTERN = '$(wifileaks[^>]*\.tsv)$';


    /**
     * main method - parse whole file and save into DB
     * @param string $file_name
     */
    public function download($file_name = "") {
        if($file_name != "") {
            try {
                $insertedRows = $this->parseData($file_name);
                // TODO: logovat bylo vlozeno $insertedRows zaznamu z wifileaks
            }
            catch(\Exception $e) {
                echo $e->getMessage();
            }
        }
        else {
            $rh = file_get_contents(self::WIFILEAKS_DOWNLOAD_DIR);
            preg_match(self::WIFILEAKS_DOWNLOAD_FILENAME_PATTERN,$rh,$matches);

            if(count($matches)) {
                $filePath = self::WIFILEAKS_DOWNLOAD_DIR . $matches[0];
                $insertedRows  = $this->parseData($filePath);
                // TODO: logovat bylo vlozeno $insertedRows zaznamu z wifileaks
            }
            else {
                // TODO: log pokus o stazeni z wigle nebyl uspesny
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
        $wifi->setSec(intval($array[2])+1);
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
            if (trim($line) != '' && $line[0] != "*") {
                $count++;
                $wifi = $this->parseLine($line);
                $wifis[] = $wifi;
            }
            if(count($wifis) == self::MULTIINSERT_ROWS) {
                $this->saveMultiInsert($wifis,self::MULTIINSERT_ROWS);
                $wifis = array();
            }
        }
        if(count($wifis)) {
            $this->saveMultiInsert($wifis,self::MULTIINSERT_ROWS);
        }
        fclose($fh);
        return $count;
    }
}

