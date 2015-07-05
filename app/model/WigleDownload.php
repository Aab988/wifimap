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

}
