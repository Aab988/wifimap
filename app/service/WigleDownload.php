<?php
namespace App\Service;
use Nette\Utils;
use Nette\Utils\DateTime;
use App\Model\Coords;
use App\Model\Wifi;


/**
 * Class WigleDownload
 * @package App\Model
 *
 */
class WigleDownload extends Download implements \IDownload {

    /** Source ID from DB  */
    const ID_SOURCE = 2;


    /** name of cookie file */
    const COOKIE_FILE = "cookie.txt";

    /** Wigle login URL */
    const WIGLE_LOGIN_URL = "https://wigle.net/api/v1/jsonLogin";

    /** Wigle download data URL */
	const WIGLE_GETDATA_URL = "https://wigle.net/api/v1/jsonSearch";


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

        //$this->loginToWigle();
        echo $this->loginToWigle();
        $query = $this->downloadQueue->getRandomNotDownloadedRecord();
        $coords = new Coords($query['lat_start'],$query['lat_end'],$query['lon_start'],$query['lon_end']);
        $results = $this->getDataFromWigle($coords, (int) $query["from"]);
        $results_decoded = json_decode($results,true);
        if($results_decoded["success"] == true) {
            $ws = $this->parseData($results_decoded);
            $this->saveAll($ws);
            $query->update(array("downloaded"=>1,"downloaded_nets_count"=>count($ws)));
            if((int)$results_decoded["resultCount"] == 100) {
                $this->downloadQueue->addRecord($coords, 0, (int)$results_decoded["last"]);
            }
        }
        else {
            echo "too many queries";
        }
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
     * @param array $line
     * @return Wifi
     */
    public function parseLine($line) {
        $wifi = new Wifi();

        $wifi->setMac($line['netid']);
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