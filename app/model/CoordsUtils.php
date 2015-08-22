<?php
/**
 * User: Roman
 * Date: 22.08.2015
 * Time: 20:18
 */
namespace App\Model;

class CoordsUtils {


    /**
     * gets doubleval of all values and sort them by size
     * return associative array
     *  ['lat_start'] - smaller latitude
     *  ['lat_end'] - bigger latitude
     *  ['lon_start'] - smaller longitude
     *  ['lon_end'] - bigger longitude
     *
     * @param string|float $lat1
     * @param string|float $lat2
     * @param string|float $lon1
     * @param string|float $lon2
     * @return array
     */
    public static function sortCoordsLatLngBySize($lat1,$lat2,$lon1,$lon2) {
        $lat1 = doubleval($lat1);
        $lat2 = doubleval($lat2);
        $lon1 = doubleval($lon1);
        $lon2 = doubleval($lon2);

        if($lat1 > $lat2) {
            $tmp = $lat2;
            $lat2 = $lat1;
            $lat1 = $tmp;
        }
        if($lon1 > $lon2) {
            $tmp = $lon2;
            $lon2 = $lon1;
            $lon1 = $tmp;
        }
        return array('lat_start'=>$lat1,'lat_end'=>$lat2,'lon_start'=>$lon1,'lon_end'=>$lon2);
    }
}