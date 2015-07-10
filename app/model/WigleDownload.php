<?php
namespace App\Model;



class WigleDownload extends Download implements \IDownload {

	private $user;
	private $password;

	public function __construct($user,$password) {
		$this->user = $user;
		$this->password = $password;
	}

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
        curl_setopt($ch, CURLOPT_URL,"https://wigle.net/gpsopen/gps/GPSDB/confirmquery");
        // how many parameters to post
        curl_setopt($ch, CURLOPT_POST, 5);
        // post parameters
        curl_setopt($ch, CURLOPT_POSTFIELDS,"longrange1=$longmin&longrange2=$longmax&latrange1=$latmin&latrange2=$latmax&simple=true");
        curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
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
        $wifi->setZdroj(2);
        
        return $wifi;

    }
    
    
    public function download() {
        $this->loginToWigle($this->user, $this->password);
        
        // TODO: sestaveni dotazu - urceni dat ktera chci stahnout
        
        
        // getDataFromWigle
        // start: 15.5,50.1

        $results = $this->getDataFromWigle(15.5, 15.8, 50.1, 50.3);

        $ws = $this->parseData($results);
        dump($ws);
        if(count($ws) > 0) {
           $this->saveAll($ws);
        }
    }
   
    
    
    private function parseData($data) {

        $matches = array();
        preg_match_all("/([A-F0-9:]{17}~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~[^~]*~\d*~\d*~[^~]*~\d*~[^~])/", $data, $matches);
        dump($matches);
        
        $ws = array();
        foreach($matches[0] as $m) {
            $ws[] = $this->parseLine($m);
        }
        
        return $ws;
    }
    
    
    private function generateQuery($longmin,$longmax,$latmin,$latmax) {
        $latitudeMax = $latmax;
        $latitudeMin = $latmin;
        
        $longtitudeMax = $longmax;
        $longtitudeMin = $longmin;
        
        $maxvals = $this->database->query("select max(latitude) as latmax, max(longtitude) as lonmax from wifi where id_zdroj=2 and longtitude between $longmin and $longmax and latitude between $latmin and $latmax")->fetch();
        if($maxvals->lonmax != null) {
            $longtitudeMin = $maxvals->lonmax;
        }
       /* if($maxvals->latmax != null) {
           $latitudeMin = $maxvals->latmax;
        }
        */
        if($longtitudeMin + 0.01 < $longtitudeMax) {
            $longtitudeMax = $longtitudeMin + 0.01;
        }
        else {
            if($latitudeMin + 0.01 < $latitudeMax) {
                $latitudeMax = $latitudeMin + 0.01;
            }
        }
        
        
        return array(
            "latmin" => $latitudeMin,
            "latmax" => $latitudeMax,
            "lonmin" => $longtitudeMin,
            "lonmax" => $longtitudeMax
        );
    }


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

        for($lat = $lat_start; $lat<$lat_end; $lat+=0.05) {
            for($lon = $lon_start; $lon<$lon_end; $lon+=0.05) {
                $a = array("lat_start"=>$lat,"lat_end"=>$lat+0.05,"lon_start"=>$lon,"lon_end"=>$lon+0.05);
                $array[] = $a;
            }

        }
        foreach($array as $key=>$ar) {
            $array[$key][] = $this->improveLatLngRange($ar["lat_start"],$ar["lat_end"],$ar["lon_start"],$ar["lon_end"]);
        }
        //$ar = $array[0];
        //echo $this->improveLatLngRange($ar["lat_start"],$ar["lat_end"],$ar["lon_start"],$ar["lon_end"]);

        dump($array);
    }

    private function improveLatLngRange($lat_start,$lat_end,$lon_start,$lon_end) {
		$pole = array();
        $pocet = $this->analyzeImage($lat_start,$lat_end,$lon_start,$lon_end);
		$pole[] = array("lat_start"=>$lat_start,"lat_end"=>$lat_end,"lon_start"=>$lon_start,"lon_end"=>$lon_end);
        if($pocet > 5000) {

            for($lat = $lat_start;$lat < $lat_end;$lat+=($lat_end-$lat_start)/(double)2) {
                for ($lon = $lon_start; $lon < $lon_end; $lon += ($lon_end - $lon_start) / (double)2) {

					$nlat = ($lat + ($lat_end - $lat_start) / (double)2);
					$nlon = ($lon + ($lon_end - $lon_start) / (double)2);
                    echo "Lat:". $lat ." - ". $nlat . "<br />";
					echo "Lon:". $lon ." - ".$nlon . "<br />";
                    $pole[] = $this->improveLatLngRange($lat, $nlat,$lon, $nlon);
                }
            }
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
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        $a = curl_exec($ch); // $a will contain all headers

        //dump($a);

        $a = explode("-url:",$a);
        $b = explode("Locat",$a[1]);
        //dump($b[1]);

        $url = "https://wigle.net".trim($b[0]);
        //echo $url;
        $a = imagecreatefrompng($url);

        $points = 0;
        for($x = 0; $x< 256; $x++) {
            for($y = 0; $y<256; $y++) {
                if(dechex(imagecolorat($a, $x,$y)) == "ff0000") {
                    $points++;
                }
                //echo dechex(imagecolorat($a, $x,$y)) . '<br />';

            }
        }

        return $points;
        //imagepng($a);



        // priklad adresy
        // https://wigle.net/gps/gps//GPSDB/onlinemap2/?lat1=50.21909462044748&long1=15.787353515625
        //	&lat2=50.21206446065373&long2=15.79833984375&redir=Y&networksOnly=Y&sizeX=256&sizeY=256

    }




}
