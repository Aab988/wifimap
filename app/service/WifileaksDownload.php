<?php
namespace App\Service;
use App\Model\Log;
use App\Model\Wifi;
use Nette\Utils\ArrayList;
use Nette\Utils\DateTime;
use Nette\Utils\Image;

class WifileaksDownload extends Download implements IDownload {

    /** @var SourceManager */
    private $sourceManager;

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
    const WIFILEAKS_DOWNLOAD_FILENAME_PATTERN = '$(wifileaks_[\d]{6}.tsv)$';

    /** wifileaks download file name date part regular expression */
    const WIFILEAKS_DOWNLOAD_FILENAME_DATE_PATTERN = '$wifileaks_([\d]{6}).tsv$';


    /**
     * main method - parse whole file and save into DB
     * @param string $file_name
     */
    public function download($file_name = "") {
        $this->sourceManager = new SourceManager($this->database);

        if($file_name != "") {
            try {
                $insertedRows = $this->parseData($file_name);
                $this->logger->addLog(new Log(Log::TYPE_INFO,'WIFILEAKS DOWNLOAD','ulozeno ' . $insertedRows . ' siti'));
            }
            catch(\Exception $e) {
                $this->logger->addLog(new Log(Log::TYPE_ERROR,'WIFILEAKS DOWNLOAD','Nepodarilo se ulozit. Zprava: ' . $e->getMessage().'/n'));
            }
        }
        else {
            $rh = file_get_contents(self::WIFILEAKS_DOWNLOAD_DIR);
            preg_match_all(self::WIFILEAKS_DOWNLOAD_FILENAME_PATTERN,$rh,$matches);

            $latest = array('date'=>0,'file'=>'');
            foreach($matches as $match) {
                preg_match(self::WIFILEAKS_DOWNLOAD_FILENAME_DATE_PATTERN,$match[0],$date);
                $dates[] = $date;
                if(intval($date[1])>$latest['date']) {
                    $latest['date'] = intval($date[1]);
                    $latest['file'] = $date[0];
                }
            }

            if($latest['file']!='') {
                $lddc = $this->sourceManager->getLatestDownloadDataByIdSource(self::ID_SOURCE);
                if($lddc) {
                    if($lddc == $latest['file']) {
                        $this->logger->addLog(new Log(Log::TYPE_INFO,'WIFILEAKS DOWNLOAD','NENALEZENA ZMENA - NEJAKTUALNEJSI SOUBOR JIZ BYL ZPRACOVAN'));
                        echo 'NENALEZENA ZMENA - NEJAKTUALNEJSI SOUBOR JIZ BYL ZPRACOVAN';
                        return;
                    }
                }

                $filePath = self::WIFILEAKS_DOWNLOAD_DIR . $latest['file'];
                $insertedRows  = $this->parseData($filePath);
                $this->logger->addLog(new Log(Log::TYPE_INFO,'WIFILEAKS DOWNLOAD','ulozeno ' . $insertedRows . ' siti'));
                $this->sourceManager->saveLatestDownloadDataByIdSource(self::ID_SOURCE,$latest['file']);
            }
            else {
                echo 'POKUS O STAZENI Z WIFILEAKS NEBYL USPESNY - ZADNY SOUBOR NEVYHOVUJE REGULARNIMU VYRAZU: ' . self::WIFILEAKS_DOWNLOAD_FILENAME_PATTERN;
                $this->logger->addLog(new Log(Log::TYPE_ERROR,'WIFILEAKS DOWNLOAD','POKUS O STAZENI Z WIFILEAKS NEBYL USPESNY - ZADNY SOUBOR NEVYHOVUJE REGULARNIMU VYRAZU: ' . self::WIFILEAKS_DOWNLOAD_FILENAME_PATTERN));
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
        $wifi->setSec(intval($array[2]));
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

