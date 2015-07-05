<?php
namespace App\Model;

class WifileaksDownload extends Download implements \IDownload {

    const ID_ZDROJ = 1;

    /**
     * rozparsuje cely soubor a vlozi data do DB
     * @param string $file_name cesta k souboru
     */
    public function download($file_name = "") {
        if($file_name != "") {
            $data = $this->parseData($file_name);
            $this->saveMultiInsert($data, 1000);
        }
    }

    /**
     * rozparsuje jeden radek a vytvori z nej Wifi objekt
     * @param $line String jeden radek k rozprasovani
     * @return Wifi objekt s wifi siti
     */
    private function parseLine($line) {
        $wifi = new Wifi();
        $array = explode("\t", $line);
        $wifi->setMac($array[0]);
        $wifi->setSsid($array[1]);
        $wifi->setSec($array[2]);
        $wifi->setLatitude(doubleval($array[3]));
        $wifi->setLongtitude(doubleval($array[4]));
        $wifi->setAltitude(doubleval($array[5]));
        $wifi->setZdroj(self::ID_ZDROJ);

        return $wifi;
    }

    /**
     * rozparsuje cely soubor
     * @param $fileName String cesta k souboru
     * @return array pole Wifi
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

