<?php
namespace App\Model;


class WigleDownload extends Download implements \IDownload {

    const ID_SOURCE = 2;
    const MAX_RESULTS_COUNT = 100;


	private $user;
	private $password;
	/**
	 * souradnice vygenerovanych ctvercu pro ulozeni do DB
	 * vytvoreni fronty pro stahovani dat
	 * - rekurzivni prochazeni vytvoreneho pole
	 */
	private $generatedCoords = array();




	/*public function __construct($user,$password) {
		$this->user = $user;
		$this->password = $password;
	}
*/
    private function loginToWigle($login, $password) {
        $ch = curl_init();
        // set the target url
        curl_setopt($ch, CURLOPT_URL,"https://wigle.net/api/v1/jsonLogin");
        // how many parameters to post
        curl_setopt($ch, CURLOPT_POST, 2);
        // post parameters
        curl_setopt($ch, CURLOPT_POSTFIELDS,"credential_0=$login&credential_1=$password");
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
         
        curl_setopt ($ch, CURLOPT_COOKIEJAR, "cookie.txt"); 
        curl_setopt ($ch, CURLOPT_COOKIEFILE, "cookie.txt");
        $result = curl_exec($ch);              
        curl_close ($ch); 
        echo $result;
    }
    
    private function getDataFromWigle($longmin, $longmax, $latmin, $latmax) {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_COOKIEJAR, "cookie.txt"); 
        curl_setopt ($ch, CURLOPT_COOKIEFILE, "cookie.txt"); 
        curl_setopt($ch, CURLOPT_URL,"https://wigle.net/api/v1/jsonSearch");
        // how many parameters to post
        curl_setopt($ch, CURLOPT_POST, 5);
        // post parameters
        curl_setopt($ch, CURLOPT_POSTFIELDS,"longrange1=$longmin&longrange2=$longmax&latrange1=$latmin&latrange2=$latmax");
        curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		///curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
       // curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $result = curl_exec($ch);
        curl_close ($ch); 
        return $result;
    }


    public function parseLine($line) {
        $wifi = new Wifi();

        $wifi->setMac($line['netid']);
        $wifi->setSsid($line['ssid']);
        $wifi->setComment(trim($line['comment']));
        $wifi->setName(trim($line['name']));
        $wifi->setType($line['type']);
        $wifi->setFreenet($line['freenet']);
        $wifi->setPaynet($line['paynet']);
        $wifi->setFirsttime(new \Nette\Utils\DateTime($line['firsttime']));
        $wifi->setLasttime(new \Nette\Utils\DateTime($line['lasttime']));
        $wifi->setFlags($line['flags']);
        $wifi->setWep($line['wep']);
        $wifi->setLatitude((double)$line['trilat']);
        $wifi->setLongtitude((double)$line['trilong']);
        $wifi->setLastupdt($line['lastupdt']);
        $wifi->setChannel((int)$line['channel']);
        $wifi->setBcninterval($line['bcninterval']);
        $wifi->setQos((int)$line['qos']);
        $wifi->setSource(self::ID_SOURCE);

        return $wifi;

    }

    /**
     * parseLine old -> wigle změnil funkčnost
     * @param $line
     * @return Wifi

    public function parseLine($line) {
        $wifi = new Wifi();
        
        $p = explode('~', $line);
        $wifi->setMac($p[0]);
        $wifi->setSsid($p[1]);
        $wifi->setComment(trim($p[2]));
        $wifi->setName(trim($p[3]));
        $wifi->setType($p[4]);
        $wifi->setFreenet($p[5]);
        $wifi->setPaynet($p[6]);
        $wifi->setFirsttime(new \Nette\Utils\DateTime($p[7]));
        $wifi->setLasttime($p[8]);
        $wifi->setFlags(trim($p[9]));
        $wifi->setWep($p[10]);
        $wifi->setLatitude((double)$p[11]);
        $wifi->setLongtitude((double)$p[12]);
        $wifi->setLastupdt($p[13]);
        $wifi->setChannel((int)$p[14]);
        $wifi->setBcninterval(trim($p[15]));
        $wifi->setQos((int)$p[16]);
        $wifi->setSource(2);
        
        return $wifi;

    }
    */
    
    public function download() {
        $this->loginToWigle($this->user, $this->password);
        
        $query = $this->database->query("select id,lat_start,lat_end,lon_start,lon_end from download_queue where downloaded=0 order by rand() limit 1")->fetch();

		dump($query);


        $results = $this->getDataFromWigle($query['lon_start'], $query['lon_end'],$query['lat_start'], $query['lat_end']);

        dump($results);

        if(json_decode($results,true)["success"]) {
            $ws = $this->parseData($results);

            dump($ws);
            $pocet = count($ws);
            if($pocet > 0) {
                /**
                 * TODO: nastavit downloaded na 1 v pripade ze bud je vic nez 0 nebo dotaz prosel ale zaroven vratil 0 siti protoze tam realne zadne nejsou
                 */
                $this->saveAll($ws);
                $this->database->query("update download_queue set downloaded=1,downloaded_nets_count=$pocet where id=". $query['id']);
            }
            echo $pocet;
        }
        else {
            echo "too many queries";
        }

	}


    /**
     *
     * parseData OLD -> Wigle změnil funkčnost
     * @param $data
     * @return array

    private function parseData($data) {

        $matches = array();
        preg_match_all("/([A-F0-9:]{17}~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~\d*~\d*~[^~]*~\d*~[^~])/", $data, $matches);
        //dump($matches);
        
        $ws = array();
        foreach($matches[0] as $m) {
            $ws[] = $this->parseLine($m);
        }
        
        return $ws;
    }
    */

    private function parseData($results) {
        $data = json_decode($results,true);
        $ws = array();
        foreach($data["results"] as $net) {
            $ws[] = $this->parseLine($net);
        }

        return $ws;

    }

	/**
	 * zadanou velkou plochu rozdeli na mensi - jejich velikost urcena hustotou siti v dane oblasti
	 * vetsi hustota v dane oblasti = rozdeleni plochy na 4 mensi
	 *
	 * @param $lat_start
	 * @param $lat_end
	 * @param $lon_start
	 * @param $lon_end
	 */
    public function generateLatLngDownloadArray($lat_start,$lat_end,$lon_start,$lon_end) {
        if($lat_end < $lat_start) {
            $tmp = $lat_start;
            $lat_start = $lat_end;
            $lat_end = $tmp;
        }

        if($lon_end < $lon_start) {
            $tmp = $lon_start;
            $lon_start = $lon_end;
            $lon_end = $tmp;
        }

        $lat_start = round($lat_start-0.005,2); $lat_end = round($lat_end+0.005,2);
        $lon_start = round($lon_start-0.005,2); $lon_end = round($lon_end+0.005,2);

        echo "lat: " . $lat_start . " - " . $lat_end . '<br />';
        echo "lon: " . $lon_start . " - " . $lon_end . '<br />';

        $array = array();
        for($lat = round($lat_start,2); round($lat,2) < round($lat_end,2); $lat+=0.05) {
            for($lon = round($lon_start,2); round($lon,2) < round($lon_end,2); $lon+=0.05) {

                $nlate = ($lat+0.05 <= $lat_end)?$lat+0.05:$lat_end;
                $nlone = ($lon+0.05 <= $lon_end)?$lon+0.05:$lon_end;

                $array[] = array("lat_start"=>$lat,"lat_end"=>$nlate,"lon_start"=>$lon,"lon_end"=>$nlone);
            }
        }

        foreach($array as $key=>$ar) {
            $array[$key] = $this->improveLatLngRange($ar["lat_start"],$ar["lat_end"],$ar["lon_start"],$ar["lon_end"]);
        }
        dump($array);

        $this->iterateArray($array);
		dump($this->generatedCoords);


        $this->saveAll2downloadQueue();

    }

    private function saveAll2downloadQueue() {
        foreach($this->generatedCoords as $coord) {
            $coord['id_source'] = 2;
            $coord['downloaded'] = 0;

            $this->database->query("insert into download_queue", $coord);
        }

    }


	/**
	 * rekurzivne prohleda vsechny rozmery pole a z jednotlivych ctvercu sestavi pole
	 *
	 * @param $nestedCoords vygenerovane pole, zmenseni ctverce = dalsi zanoreni
	 */
	private function iterateArray($nestedCoords) {
        foreach($nestedCoords as $c) {
            if(is_array($c) && !$this->isAssoc($c)) {
                $this->iterateArray($c);
            }
            else {
				$this->generatedCoords[] = $c;
            }
        }
    }

	/**
	 * vrati jestli je pole asociativni (ma vsechny indexy jako string)
	 * @param $array
	 * @return bool
	 */
    private function isAssoc($array) {
        $return = true;
        foreach(array_keys($array) as $a) {
            $return = $return & is_string($a);
        }
        return (bool)$return;
    }

	/**
	 * sestavi pole ctvercu obsahujici mensi mnozstvi siti pro stahovani
	 * kazde zanoreni = zmenseni ctverce
	 * v pripade ze zkoumana plocha obsahuje vice nez 5000 siti tak se rozdeli na 4 mensi plochy
	 * a pro ne se rekurzivne opakuje deleni do doby nez kazda plocha v poli obsahuje mene nez 5000 siti
	 *
	 * @param $lat_start
	 * @param $lat_end
	 * @param $lon_start
	 * @param $lon_end
	 * @return array
	 */
    private function improveLatLngRange($lat_start,$lat_end,$lon_start,$lon_end) {
		$pole = array();
        $pocet = $this->analyzeImage($lat_start,$lat_end,$lon_start,$lon_end);
        $pocet = 10;
        if($pocet > 1000) {

            for($lat = round($lat_start,6); round($lat,6) < round($lat_end,6); $lat += ($lat_end - $lat_start)/2.0) {
                for ($lon = round($lon_start,6); round($lon,6) < round($lon_end,6); $lon += ($lon_end - $lon_start) / 2.0) {

					$nlat = ($lat + ($lat_end - $lat_start) / (double)2);
					$nlon = ($lon + ($lon_end - $lon_start) / (double)2);

					$nlat = ($nlat <= $lat_end) ? $nlat : $lat_end;
					$nlon = ($nlon <= $lon_end) ? $nlon : $lon_end;

                    $pole[] = $this->improveLatLngRange($lat, $nlat,$lon, $nlon);
                }
            }
        }
        else {
            $pole = array("lat_start"=>$lat_start,"lat_end"=>$lat_end,"lon_start"=>$lon_start,"lon_end"=>$lon_end, 'calculated_nets_count'=>$pocet);
        }
        return $pole;
    }


    /**
     * vraci pocet pixelu v jine barve(hustotu siti v dane plose)
     *
     * @param $lat_start
     * @param $lat_end
     * @param $lon_start
     * @param $lon_end
     * @return int
     */
    private function analyzeImage($lat_start,$lat_end,$lon_start,$lon_end) {

        $url = "https://wigle.net/gps/gps/GPSDB/onlinemap2/";

        if($lat_end < $lat_start) {
            $tmp = $lat_start;
            $lat_start = $lat_end;
            $lat_end = $tmp;
        }

        if($lon_end < $lon_start) {
            $tmp = $lon_start;
            $lon_start = $lon_end;
            $lon_end = $tmp;
        }

        $url.= "?lat1=$lat_start&long1=$lon_start&lat2=$lat_end&long2=$lon_end";
        $url.= "&redir=Y&networksOnly=Y&sizeX=256&sizeY=256";

        //header('Content-Type: image/png');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        curl_exec($ch);

        $headers = get_headers($url,1);

        $url = "https://wigle.net".trim($headers["-url"]);
        $image = imagecreatefrompng($url);

        $points = 0;
        for($x = 0; $x< 256; $x++) {
            for($y = 0; $y<256; $y++) {
                if(dechex(imagecolorat($image, $x,$y)) != "7a000000") {
                    $points++;
                }
                //echo dechex(imagecolorat($image, $x,$y)) . '<br />';
            }
        }
        return $points;

        // priklad adresy
        // https://wigle.net/gps/gps//GPSDB/onlinemap2/?lat1=50.21909462044748&long1=15.787353515625
        //	&lat2=50.21206446065373&long2=15.79833984375&redir=Y&networksOnly=Y&sizeX=256&sizeY=256

    }

    public function setUser($user) {
        $this->user = $user;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

}
