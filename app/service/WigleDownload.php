<?php
namespace App\Service;
use App\Model\DownloadImport;
use App\Model\Log;
use App\Model\MyUtils;
use Nette\Database\SqlLiteral;
use Nette\Utils;
use Nette\Utils\DateTime;
use App\Model\Coords;
use App\Model\Wifi;


/**
 * Class WigleDownload
 * @package App\Model
 *
 */
class WigleDownload extends Download implements IDownload {

    /** Source ID from DB  */
    const ID_SOURCE = 2;

    /** name of cookie file */
    const COOKIE_FILE = "cookie.txt";

    /** Wigle login URL */
    const WIGLE_LOGIN_URL = "https://wigle.net/api/v1/jsonLogin";

    /** Wigle download data URL */
	const WIGLE_GETDATA_URL = "https://wigle.net/api/v1/jsonSearch";
    /** Wigle get observations URL  */
    const WIGLE_OBSERVATIONS_URL = "https://wigle.net/api/v1/jsonLocation";


    const WIGLE_MAXIMUM_ROWS = 100;

    /** @var string Wigle login */
    private $user = "";

    /** @var string Wigle password */
    private $password = "";



    /**
     * @var WigleDownloadQueue
     */
    public $downloadQueue;

    /**
     * main method, performed by CRON
     */
    public function download() {

        $this->loginToWigle();
        $query = $this->downloadQueue->getRandomNotDownloadedRecord();
        if(!$query) {
            return;
        }
        $id_download_request = $query->id_download_request;
        $coords = new Coords($query['lat_start'],$query['lat_end'],$query['lon_start'],$query['lon_end']);
        $results = $this->getDataFromWigle($coords, (int) $query["from"]);
        $results_decoded = json_decode($results,true);
        if($results_decoded["success"] == true) {

            // rozparsovat data
            $data = $this->parseData($results_decoded);
            $mac_addresses = array();
            // vybrat vsechny mac adresy a body nastavit jako hrube
            foreach($data as $w) {
                $mac_addresses[] = $w->getMac();
                $w->setCalculated(1);
            }

            // ulozit mac adresy k dalsimu zpracovani
            $this->saveAll2WigleAps($query['id'],$mac_addresses);
            // ulozit vypoctene body
            $this->saveAll($data);

            // transakcne vlozime
            $this->database->beginTransaction();
            $query->update(array(
                "downloaded"=>1,
                "downloaded_nets_count"=>count($mac_addresses),
                "count_downloaded_observations"=>0,
            ));

            if((int)$results_decoded["resultCount"] >= self::WIGLE_MAXIMUM_ROWS) {
                $this->downloadQueue->addRecord($coords, 0, $id_download_request, (int)$results_decoded["last"]);
                $this->database->table('download_request')
                    ->where('id',$id_download_request)
                    ->update(array('total_count'=>new SqlLiteral('total_count + 1')));
            }
            $this->database->commit();
        }
        else {
            $this->logger->addLog(new Log(Log::TYPE_WARNING,'WIGLE DOWNLOAD','moc pozadavku'));
        }
    }


    public function downloadObservations() {
        $this->loginToWigle();

        $ap = $this->database->table('wigle_aps')
            ->where('downloaded',0)
            ->order('priority DESC, rand()')
            ->limit(1)
            ->fetch();

        $observationsWigle = $this->sendCurlRequest(self::WIGLE_OBSERVATIONS_URL,array('netid'=>$ap['mac']));

        $observationsDecoded = json_decode($observationsWigle,true);

        dump($observationsDecoded);

        $wifis = array();

        foreach($observationsDecoded['result'] as $r) {
            foreach($r['locationData'] as $o) {
                $wifi = new Wifi();
                $wifi->setAltitude($o['alt']);
                $wifi->setAccuracy($o['accuracy']);
                $wifi->setBcninterval($r['bcninterval']);
                $wifi->setChannel($r['channel']);
                $wifi->setComment($r['comment']);
                $wifi->setDateAdded(new DateTime());
                $wifi->setFirsttime($r['firsttime']);
                $wifi->setFlags($r['flags']);
                $wifi->setFreenet($r['freenet']);
                $wifi->setLasttime($o['time']);
                $wifi->setLastupdt($o['lastupdt']);
                $wifi->setLatitude($o['latitude']);
                $wifi->setLongitude($o['longitude']);
                $wifi->setMac($o['netid']);
                $wifi->setName($o['name']);
                $wifi->setPaynet($r['paynet']);
                $wifi->setQos($r['qos']);
                $wifi->setSource(WigleDownload::ID_SOURCE);
                $wifi->setSsid($o['ssid']);
                $wifi->setType($r['type']);
                $wifi->setWep($o['wep']);
                $wifis[] = $wifi;
            }
        }

        $this->database->beginTransaction();
        $rows = $this->saveAll($wifis);
        $id_wigle_download_queue = $ap['id_wigle_download_queue'];


        // stazeno z wigle -> v najdem pokud je v donwload importu tak pridame do google
        $this->database->table(DownloadImportService::TABLE)
            ->where('id_wigle_aps',$ap['id'])
            ->where('state',1)
            ->update(array('state'=>DownloadImport::DOWNLOADED_WIGLE));


        $idGR = null;

        $googleDownloadService = new GoogleDownload($this->database);
        if ($rows) foreach($rows as $row) {
            $w = Wifi::createWifiFromDBRow($row);
            if($w) {
                $idGR = $googleDownloadService->createRequestFromWifi($w,2);
            }
        }

        $this->database->table(DownloadImportService::TABLE)
            ->where('id_wigle_aps',$ap['id'])
            ->where('state',DownloadImport::DOWNLOADED_WIGLE)
            ->where('id_google_request',null)
            ->update(array(
                'state'=>DownloadImport::ADDED_GOOGLE,
                'id_google_request' => $idGR
            ));

        $ap->update(array('downloaded'=>1,'downloaded_date'=>new DateTime()));
        if($id_wigle_download_queue) {
            $this->database->table('wigle_download_queue')
                ->where('id',$id_wigle_download_queue)
                ->update(array('count_downloaded_observations'=>new SqlLiteral('count_downloaded_observations + 1')));
        }
        $this->database->commit();
        // PO UPDATE wigle_download_queue se pomoci DB triggeru zmeni hodnoty u download_requestu
        // a pokud je ten reuqest jiz dokoncen tak i requesty ktere cekaly na ten dany request
        dump($wifis);


    }

    /**
     * create and exec CURL request
     *
     * @param string $url
     * @param array $params associative array, key - param name, value - param value
     * @param bool|true $withCookie
     * @param bool $withHeader
     * @return mixed CURL result
     */
	private function sendCurlRequest($url,$params,$withCookie = true,$withHeader = false) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
        if($withHeader) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
		if($withCookie) {
			curl_setopt ($ch, CURLOPT_COOKIEJAR, self::COOKIE_FILE);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, self::COOKIE_FILE);
		}
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}


	/**
	 * Login to Wigle and save cookie
	 */
    private function loginToWigle() {
		$this->sendCurlRequest(self::WIGLE_LOGIN_URL,array("credential_0"=>$this->user,"credential_1"=>$this->password));
    }


    /**
     * @param Coords $coords
     * @param int $first
     * @return string JSON with Wigle data
     */
    private function getDataFromWigle($coords, $first = 0) {
        $arr = array(
            "longrange1"=>$coords->getLonStart(),
            "longrange2"=>$coords->getLonEnd(),
            "latrange1"=>$coords->getLatStart(),
            "latrange2"=>$coords->getLatEnd()
        );
        if($first != 0) {
            $arr["first"] = $first;
            $arr["last"] = $first + 99;
        }
		return $this->sendCurlRequest(self::WIGLE_GETDATA_URL,$arr);
    }

    /**
     * @param array $data decoded JSON
     * @return Wifi[]
     */
    private function parseData($data) {
        $ws = array();
        foreach($data["results"] as $net) {
            $ws[] = $this->parseLine($net);
        }
        return $ws;
    }

    /**
     * @param array $data decoded JSON
     * @return array MAC addresses array
     */
    private function parseData2MacAddresses($data) {
        $ws = array();
        foreach($data["results"] as $net) {
            $ws[] = MyUtils::macSeparator2Colon($net['netid']);
        }
        return $ws;
    }

    /**
     * @param int $id_wigle_download_queue
     * @param array $mac_addresses MAC addresses array
     * @param int $priority
     */
    public function saveAll2WigleAps($id_wigle_download_queue,$mac_addresses,$priority = 1) {
        $this->database->beginTransaction();
        foreach($mac_addresses as $ma) {
           $this->save2WigleAps($id_wigle_download_queue,$ma,$priority);
        }
        $this->database->commit();
    }

    /**
     * @param int $id_wigle_download_queue
     * @param string $mac_address
     * @param int $priority
     * @return bool|int|\Nette\Database\Table\IRow
     */
    public function save2WigleAps($id_wigle_download_queue,$mac_address,$priority = 1) {
        $array = array(
            'id_wigle_download_queue'=>$id_wigle_download_queue,
            'mac'=>$mac_address,
            'created'=>new DateTime(),
            'downloaded'=>0,
            'priority'=>$priority
        );
        $alreadyExisting = $this->database->table("wigle_aps")
            ->where("mac",$mac_address)
            ->where("downloaded",0)->fetch();

        if($alreadyExisting) {
            $this->database->table("wigle_aps")
                ->where("id",$alreadyExisting['id'])
                ->update(array('priority'=>$priority));
            $row = $this->database->table("wigle_aps")->where("id",$alreadyExisting["id"])->fetch();
        }
        else {
            $row = $this->database->table('wigle_aps')->insert($array);
        }
        return $row;
    }



    /**
     * get count of not downloaded records in wigle_aps table where priority is >= $priority
     *
     * @param null|int $priority
     * @return int
     */
    public function getWigleApsCount($priority = null) {
        $count = $this->database->table('wigle_aps')
            ->where('downloaded',0);
        if($priority) $count->where('priority >= ?',$priority);
        return $count->count();
    }



    /**
     * @param array $line
     * @return Wifi
     */
    public function parseLine($line) {
        $wifi = new Wifi();

        $wifi->setMac(MyUtils::macSeparator2Colon($line['netid']));
        $wifi->setSsid(($line['ssid']) ? $line['ssid'] : "");
        $wifi->setComment(trim($line['comment']));
        $wifi->setName(trim($line['name']));
        $wifi->setType($line['type']);
        $wifi->setFreenet($line['freenet']);
        $wifi->setPaynet($line['paynet']);
        $wifi->setFirsttime(new DateTime($line['firsttime']));
        $wifi->setLasttime(new DateTime($line['lasttime']));
        $wifi->setFlags($line['flags']);
        $wifi->setWep($line['wep']);
        $wifi->setLatitude((double)$line['trilat']);
        $wifi->setLongitude((double)$line['trilong']);
        $wifi->setLastupdt($line['lastupdt']);
        $wifi->setChannel((int)$line['channel']);
        $wifi->setBcninterval($line['bcninterval']);
        $wifi->setQos((int)$line['qos']);
        $wifi->setSource(self::ID_SOURCE);

        return $wifi;
    }


    /**
     * @param string $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }



}