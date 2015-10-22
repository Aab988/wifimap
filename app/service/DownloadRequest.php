<?php
/**
 * User: Roman
 * Date: 23.08.2015
 * Time: 22:26
 */
namespace App\Service;
use Nette;
use Nette\Utils\DateTime;
use App\Model\Coords;


class DownloadRequest extends BaseService {

    /** ERROR/INFO returned CONSTANTS */

    const STATE_ERR_ALREADY_IN_QUEUE = "ALREADY_IN_QUEUE";
    const STATE_ERR_RECENTLY_DOWNLOADED = "RECENTLY_DOWNLOADED";
    const STATE_SUCCESS_ADDED_TO_QUEUE = "ADDED_TO_QUEUE";


    const DIVIDE_AREA_ONLY_NOT_IN_QUEUE = true;


    /**
     * adds download request to wigle requests queue to DB
     *
     * @param Coords $coords
     * @param int       $idSource
     */
    public function addDownloadRequest($coords, $idSource) {
        $this->database->query("insert into download_request", array(
            "id_source" => $idSource,
            "date"=> new DateTime(),
            "lat_start" => $coords->getLatStart(),
            "lat_end" => $coords->getLatEnd(),
            "lon_start" => $coords->getLonStart(),
            "lon_end" => $coords->getLonEnd(),
            "processed" => 'N'
        ));
    }


    /**
     * @param int $idSource
     * @return array|Nette\Database\Table\IRow[]
     */
    public function getAllRequestsByIdSource($idSource) {
        return $this->database->query("select dr.* from download_request dr
INNER JOIN
(select lat_start,lat_end,lon_start,lon_end,MAX(date) AS MaxDateTime FROM download_request GROUP BY lat_start,lat_end,lon_start,lon_end) groupeddr
ON dr.lat_start = groupeddr.lat_start AND dr.lat_end = groupeddr.lat_end AND dr.lon_start = groupeddr.lon_start
AND dr.lon_end = groupeddr.lon_end
AND dr.date = groupeddr.MaxDateTime
")->fetchAll();


        /*return $this->database->table("download_request")->where("id_source", $idSource)
            ->fetchAll();*/
    }




    /**
     * finds request already existing in this area in wigle request queue
     * only request which is whole in passed area
     *
     * @param Coords $coords
     * @param int       $idSource
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    private function getRequestAlreadyExistingInLatLngRange($coords, $idSource) {
        $query = $this->database->table("download_request")
            ->where("id_source",$idSource)
            ->where("lat_start <= ?",$coords->getLatStart())
            ->where("lat_end >= ?", $coords->getLatEnd())
            ->where("lon_start <= ?",$coords->getLonStart())
            ->where("lon_end >= ?", $coords->getLonEnd());
        return $query->fetch();
    }

    /**
     * process wigle request and determine what to do
     * returns status code
     *
     * @param Coords $coords
     * @param int       $idSource
     * @return bool|string
     */
    public function processDownloadRequestCreation($coords, $idSource) {

        $coords = new Coords(
            round($coords->getLatStart()-0.005,2),
            round($coords->getLatEnd()+0.005,2),
            round($coords->getLonStart()-0.005,2),
            round($coords->getLonEnd()+0.005,2));

        $existingRequest = $this->getRequestAlreadyExistingInLatLngRange($coords,$idSource);

        if($existingRequest) {
            // nalezeno
            $er = $existingRequest->toArray();
            // pokud processed = N pak je ve fronte a neni stazeno
            if($er["processed"] == "N") {
                return self::STATE_ERR_ALREADY_IN_QUEUE;
            }
            else {
                $today = new DateTime();
                $diff = $today->diff($er["processed_date"]);
                if($diff->days < 7) {
                    return self::STATE_ERR_RECENTLY_DOWNLOADED;
                }
                else {
                    $this->addDownloadRequest($coords,$idSource);
                    return self::STATE_SUCCESS_ADDED_TO_QUEUE;
                }
            }
        }
        else {
            if(self::DIVIDE_AREA_ONLY_NOT_IN_QUEUE) {
                $rects = $this->findNotInQueueRectsInLatLngRange($coords,$idSource);
                foreach($rects as $rect) {
                    $this->addDownloadRequest($rect,$idSource);
                }
                if(count($rects) > 0) {
                    return self::STATE_SUCCESS_ADDED_TO_QUEUE;
                }
                else {
                    return self::STATE_ERR_ALREADY_IN_QUEUE;
                }
            }
            else {
                $this->addDownloadRequest($coords,$idSource);
                return self::STATE_SUCCESS_ADDED_TO_QUEUE;
            }
        }
    }

    /**
     * @param int $idSource
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    public function getEldestDownloadRequest($idSource) {
        return $this->database->table("download_request")
            ->select('id,lat_start,lat_end,lon_start,lon_end')
            ->where('processed', 'N')
            ->where('id_source',$idSource)
            ->order('date ASC')
            ->fetch();
    }


    /**
     * get all nets trespassing to area created by latitude and longitude range
     *
     * @param Coords $coords
     * @param int       $idSource
     * @return array|Nette\Database\Table\IRow[]
     */
    private function getAllRequestsInLatLngRange($coords, $idSource) {
        return $this->database->table("download_request")
            ->select("lat_start,lat_end,lon_start,lon_end")
            ->where('id_source',$idSource)
            ->where('lat_start >= ? OR lat_end <= ?', $coords->getLatStart(),$coords->getLatEnd())
            ->where('lon_start >= ? OR lon_end <= ?', $coords->getLonStart(),$coords->getLonEnd())
            ->where('processed','N')
            ->fetchAll();
    }


    /**
     * finds rectangles which are not in existing query
     * - user create query with latitude and longitude range,
     * but we can have other request trespassing this download request
     * so this method will find latitude,longitude ranges which are not in any other query, segment them to rectangles
     * and return
     *
     * @param Coords $coords
     * @param int       $idSource
     * @return array
     */
    public function findNotInQueueRectsInLatLngRange($coords, $idSource) {

        // get all requests in this range
        $data = $this->getAllRequestsInLatLngRange($coords,$idSource);


        // create latitude, longitude XY mapping
        $mapping = $this->createMappingXY($data,$coords);
        $mappingX = $mapping['xMap'];
        $mappingY = $mapping['yMap'];

        // create mapepd array
        $array = $this->createMappedArray($data,$mappingX,$mappingY);
        // find rectangles
        $rects = $this->findRectanglesIn01Array($array);
        // unmap rectangles back to latitude and longitude
        $unmapped = $this->unmapArrayOfRectanglesFromIndexToLatLng($rects,$mappingX,$mappingY);

        return $unmapped;
    }


    /**
     * @param Nette\Database\Table\IRow $request
     */
    public function setProcessed($request) {
        $request->update(array(
            'processed' => 'Y',
            'processed_date' => new DateTime()
        ));
    }

    /**
     * unmap array back from indexes to latitude and longitude
     *
     * @param array $rectangles return from findRectanglesIn01Array() method
     * @param array $mappingX return from createMappingXY() method
     * @param array $mappingY return from createMappingXY() method
     * @return Coords[]
     */
    private function unmapArrayOfRectanglesFromIndexToLatLng($rectangles,$mappingX,$mappingY) {
        $unmapped = array();
        for($y = 0; $y < count($rectangles); $y++) {
            foreach($rectangles[$y] as $zero) {
                $unmapped[] = new Coords($mappingX[$zero["from"]],$mappingX[$zero['to']],$mappingY[$y],$mappingY[$y+1]);
            }
        }
        return $unmapped;
    }

    /**
     *  return finded rectangles with 0 value
     *  from is always first index, to is from + zero count
     *  FE:
     *  1 1 0 0 0 => rect (from 2 to 5)
     *  0 0 1 1 0 => rect (from 0 to 2) and (from 4 to 5)
     *  1 0 1 1 1 => rect (from 1 to 2)
     *  1 1 1 0 0 => rect (from 3 to 5)
     *
     * @param array $array
     * @return array
     */
    private function findRectanglesIn01Array($array) {
        $rects = array();
        for($x = 0; $x < count($array); $x++) {
            $ar = array();$nul = 0; $one = 0;$from = 0;
            for($y = 0; $y < count($array[0]); $y++) {
                if($array[$x][$y] == 0) {
                    if($one > 0) {$from = $y;$one = 0;}
                    $nul++;
                }
                else {
                    if($nul > 0) {$ar[] = array("from" => $from, "to" => $from + $nul);$nul = 0;}
                    $one++;
                }
            }
            if($nul > 0) {
                $ar[] = array("from" => $from, "to" => $from + $nul);
            }
            $rects[$x] = $ar;
        }
        return $rects;
    }


    /**
     * creates an mapped array - where isnt latitude and longitude indexes (its mapped before)
     * value 0 means that in the given mapped segment isn't existing request
     * value 1 means that there is an existing request
     *
     * @param array|\Nette\Database\Table\IRow[] $data
     * @param array|float[] $mappingX
     * @param array|float[] $mappingY
     * @return array
     */
    private function createMappedArray($data,$mappingX,$mappingY) {
        $array = array();
        for($x = 0; $x < count($mappingX)-1;$x++) {
            for($y = 0; $y < count($mappingY)-1; $y++) {

                $coords = new Coords($mappingX[$x],$mappingX[$x + 1],$mappingY[$y],$mappingY[$y + 1]);
                $val = 0;
                foreach($data as $d) {
                    if($coords->getLatStart() >= doubleval($d['lat_start']) && $coords->getLatEnd() <= doubleval($d['lat_end'])
                        && $coords->getLonStart() >= doubleval($d['lon_start']) && $coords->getLonEnd() <= doubleval($d['lon_end'])) {
                        $val = 1;
                        continue;
                    }
                }
                $array[$y][$x] = $val;
            }
        }
        return $array;
    }

    /**
     * create X,Y array MAP
     * index to lat or lng
     *
     * @param array|\Nette\Database\Table\IRow[] $data
     * @param Coords $coords
     * @return array
     */
    private function createMappingXY($data,$coords) {
        $mappingX = array($coords->getLatStart(),$coords->getLatEnd());
        $mappingY = array($coords->getLonStart(),$coords->getLonEnd());
        foreach($data as $d) {
            $d_coords = new Coords($d['lat_start'],$d['lat_end'],$d['lon_start'],$d['lon_end']);

            if(!in_array($d_coords->getLatStart(),$mappingX) && $d_coords->getLatStart() >= $coords->getLatStart() && $d_coords->getLatStart() <= $coords->getLatEnd()) {
                $mappingX[] = $d_coords->getLatStart();
            }
            if(!in_array($d_coords->getLatEnd(),$mappingX) && $d_coords->getLatEnd() <= $coords->getLatEnd() && $d_coords->getLatEnd() >= $coords->getLatStart()) {
                $mappingX[] = $d_coords->getLatEnd();
            }
            if(!in_array($d_coords->getLonStart(),$mappingY) && $d_coords->getLonStart() >= $coords->getLonStart() && $d_coords->getLonStart() <= $coords->getLonEnd()) {
                $mappingY[] = $d_coords->getLonStart();
            }
            if(!in_array($d_coords->getLonEnd(),$mappingY) && $d_coords->getLonEnd() <= $coords->getLonEnd() && $d_coords->getLonEnd() >= $coords->getLonStart()) {
                $mappingY[] = $d_coords->getLonEnd();
            }
        }
        sort($mappingX);
        sort($mappingY);

        return array('xMap'=>$mappingX,'yMap'=>$mappingY);
    }


}