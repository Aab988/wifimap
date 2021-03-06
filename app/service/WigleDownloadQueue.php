<?php
/**
 * User: Roman
 * Date: 23.08.2015
 * Time: 21:12
 */
namespace App\Service;
use Nette;
use App\Model\Coords;
use App\Model\ArrayUtil;

class WigleDownloadQueue extends BaseService {

    /** Wigle QOS grad color image URL */
    const WIGLE_QOS_GRAD_IMAGE_URL = "https://wigle.net/images/qos_grad.png";
    /** Maximum results returned by wigle to one query */
    const MAX_RESULTS_COUNT = 500;
    /** Wigle download overlay image URL */
    const WIGLE_IMAGE_OVERLAY_URL = "https://wigle.net/gps/gps/GPSDB/onlinemap2/";
    /** Wigle web url */
    const WIGLE_URL = "https://wigle.net";

    /** @var array wigle wifi colors */
    private $wigleNetColors = array();
    /** @var array coords generated by cron queue creation script */
    private $generatedCoords = array();

    /**
     * return one random not downloaded record from queue
     *
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    public function getRandomNotDownloadedRecord() {
        return $this->database->table("wigle_download_queue")->select("id,id_download_request,lat_start,lat_end,lon_start,lon_end,from,to")
            ->where("downloaded = ?", 0)
            //->order("rand()")
            ->limit(1)
            ->fetch();
    }

    /**
     * add one record to download queue
     *
     * @param Coords $coords
     * @param int    $calculated_nets_count
     * @param int $id_download_request
     * @param int    $from
     */
    public function addRecord($coords, $calculated_nets_count,$id_download_request=null, $from = 0) {
       // dump($id_download_request);
        $data = array(
            "lat_start" => $coords->getLatStart(),
            "lat_end" => $coords->getLatEnd(),
            "lon_start" => $coords->getLonStart(),
            "lon_end" => $coords->getLonEnd(),
            "calculated_nets_count" => $calculated_nets_count,
            "downloaded" => 0,
            "id_download_request"=>$id_download_request
        );
        if($from != 0) {
            $data["from"] = $from;
            $data["to"] = $from + WigleDownload::WIGLE_MAXIMUM_ROWS-1;
        }
        $this->database->table('wigle_download_queue')->insert($data);
    }

    /**
     * divide big area into smaller ones by counted sites density
     *
     * @param Coords $coords
     */
    public function generateLatLngDownloadArray($coords) {
        $this->fillWigleNetColors();

        $coords = $this->divideLatLngInitially($coords);

        $i = 0;
        foreach($coords as $key=>$ar) {
            $coords[$key] = $this->improveLatLngRange($ar);
            $i++;
        }
        $this->iterateArray($coords);
    }

    /**
     * @param null|int $iddr
     */
    public function save($iddr = null) {
        $this->saveAll2downloadQueue($iddr);
    }

    /**
     * get colors from wigle net qos grad image
     */
    private function fillWigleNetColors() {
        $image = imagecreatefrompng(self::WIGLE_QOS_GRAD_IMAGE_URL);
        for($x = 0; $x< 100; $x++) {
            for($y = 0; $y<12; $y++) {
                $color = dechex(imagecolorat($image, $x,$y));
                if(!in_array($color,$this->wigleNetColors)) {
                    $this->wigleNetColors[] = $color;
                }
            }
        }
        $this->wigleNetColors[] = "ff0000";
        $this->wigleNetColors[] = "00ff00";
    }

    /**
     * do basic segmentation (by 0.05 in lat and lon range) of lat lng rectangle
     * @param Coords $coords
     * @return Coords[]
     */
    private function divideLatLngInitially($coords)
    {
        $ncoords = array();
        for ($lat = round($coords->getLatStart(), 2); round($lat, 2) < round($coords->getLatEnd(), 2); $lat += 0.05) {
            for ($lon = round($coords->getLonStart(), 2); round($lon, 2) < round($coords->getLonEnd(), 2); $lon += 0.05) {

                $nlate = ($lat + 0.05 <= $coords->getLatEnd()) ? $lat + 0.05 : $coords->getLatEnd();
                $nlone = ($lon + 0.05 <= $coords->getLonEnd()) ? $lon + 0.05 : $coords->getLonEnd();
                $ncoords[] = new Coords($lat,$nlate,$lon,$nlone);
            }
        }
        return $ncoords;
    }

    /**
     *
     * recursively creating array of quads with less nets to download
     * each nesting = quad segmentation to 4 smaller quads
     *
     * @param Coords $coords
     * @return array
     */
    private function improveLatLngRange($coords) {
        $nc = array();
        $count = $this->analyzeImage($coords);
        //$this->logger->addLog("count-colors","count: $count",true);

        $strLonEnd = (string)$coords->getLonEnd();
        $strLonEnd = explode('.',$strLonEnd);
        $strLonEndLen = strlen($strLonEnd[1]);

        $strLatEnd = (string)$coords->getLatEnd();
        $strLatEnd = explode('.',$strLatEnd);
        $strLatEndLen = strlen($strLatEnd[1]);

        if($count > self::MAX_RESULTS_COUNT && ($strLatEndLen <= 6 || $strLonEndLen <= 6)) {

            for($lat = round($coords->getLatStart(),6); round($lat,6) < round($coords->getLatEnd(),6); $lat += ($coords->getLatEnd() - $coords->getLatStart())/2.0) {
                for ($lon = round($coords->getLonStart(),6); round($lon,6) < round($coords->getLonEnd(),6); $lon += ($coords->getLonEnd() - $coords->getLonStart()) / 2.0) {

                    $nlat = ($lat + ($coords->getLatEnd() - $coords->getLatStart()) / 2.0);
                    $nlon = ($lon + ($coords->getLonEnd() - $coords->getLonStart()) / 2.0);

                    $nlat = ($nlat <= $coords->getLatEnd()) ? $nlat : $coords->getLatEnd();
                    $nlon = ($nlon <= $coords->getLonEnd()) ? $nlon : $coords->getLonEnd();

                    $nc[] = $this->improveLatLngRange(new Coords($lat, $nlat,$lon, $nlon));
                }
            }
        }
        else {
            $nc = array('coords'=>new Coords($coords->getLatStart(),$coords->getLatEnd(),$coords->getLonStart(),$coords->getLonEnd()),'calculated_nets_count'=>$count);
        }
        return $nc;
    }

    /**
     * return number of nets on Wigle overlay Image
     *
     * @param Coords $coords
     * @return int
     */
    private function analyzeImage($coords) {
        // TODO: ziskavat i jinak celky obrazek nez ctverec pokud mam jiny rozsah souradnic nez ctverec?
        $params = array(
            "lat1"=>$coords->getLatStart(),
            "long1"=>$coords->getLonStart(),
            "lat2"=>$coords->getLatEnd(),
            "long2"=>$coords->getLonEnd(),
            "redir"=>"Y",
            "networksOnly"=>"Y",
            "sizeX"=>256,
            "sizeY"=>256
        );
        $url = self::WIGLE_IMAGE_OVERLAY_URL . "?". http_build_query($params);

        $headers = get_headers($url,1);

        $url = self::WIGLE_URL.trim($headers["Location"]);
        $image = imagecreatefrompng($url);

        $points = 0;
        for($x = 0; $x< 256; $x++) {
            for($y = 0; $y<256; $y++) {
                if(in_array(dechex(imagecolorat($image, $x,$y)),$this->wigleNetColors)) {
                    $points++;
                }
            }
        }
        return $points;
    }

    /**
     * recursively search whole array and create array from each quads
     *
     * @param array $nestedCoords nested array -> nesting = quad segmentation
     */
    private function iterateArray($nestedCoords) {
        foreach($nestedCoords as $c) {
            if(is_array($c) && !ArrayUtil::isAssoc($c)) {
                $this->iterateArray($c);
            }
            else {
                $this->generatedCoords[] = $c;
            }
        }
    }

    /**
     * save all generated coords into DB
     * @param int $id_download_request
     */
    private function saveAll2downloadQueue($id_download_request = null) {
        foreach($this->generatedCoords as $coord) {
            $this->addRecord($coord['coords'], $coord['calculated_nets_count'], $id_download_request);
        }
        $this->generatedCoords = array();
    }


    /**
     * @return array
     */
    public function getGeneratedCoords()
    {
        return $this->generatedCoords;
    }

}