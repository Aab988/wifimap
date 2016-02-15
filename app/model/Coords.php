<?php
/**
 * User: Roman
 * Date: 23.08.2015
 * Time: 20:03
 */
namespace App\Model;
class Coords {

    /** @var float $lat_start */
    private $lat_start;
    /** @var float $lat_end */
    private $lat_end;
    /** @var float $lon_start */
    private $lon_start;
    /** @var float $lon_end */
    private $lon_end;

    /**
     * @param string|float $lat1
     * @param string|float $lat2
     * @param string|float $lon1
     * @param string|float $lon2
     */
    public function __construct($lat1=0.0,$lat2=0.0,$lon1=0.0,$lon2=0.0) {
        $this->sortCoordsLatLngBySize($lat1,$lat2,$lon1,$lon2);
    }

    /**
     * gets doubleval of all values and sort them by size and set as object values
     *
     * @param string|float $lat1
     * @param string|float $lat2
     * @param string|float $lon1
     * @param string|float $lon2
     * @return array
     */
    public function sortCoordsLatLngBySize($lat1,$lat2,$lon1,$lon2) {
        $this->lat_start = (doubleval($lat1) < doubleval($lat2)) ? doubleval($lat1) : doubleval($lat2);
        $this->lat_end = (doubleval($lat2) > doubleval($lat1)) ? doubleval($lat2) : doubleval($lat1);
        $this->lon_start = (doubleval($lon1) < doubleval($lon2)) ? doubleval($lon1) : doubleval($lon2);
        $this->lon_end = (doubleval($lon2) > doubleval($lon1)) ? doubleval($lon2) : doubleval($lon1);
    }

    /**
     * sort latitude and longitude by size
     */
    public function sortBySize() {
        $this->sortCoordsLatLngBySize($this->lat_start,$this->lat_end,$this->lon_start,$this->lon_end);
    }

    /**
     * get absolute value of latitude difference (delta latitude)
     * @return number
     */
    public function getDeltaLat() {
        return abs($this->lat_end - $this->lat_start);
    }

    /**
     * get absolute value of longitude difference (delta longitude)
     * @return number
     */
    public function getDeltaLon() {
        return abs($this->lon_end - $this->lon_start);
    }

    /**
     * enlarge latitude range by $k * deltaLatitude
     * @param float $k
     */
    public function increaseLatRange($k) {
        $deltaLat = $this->getDeltaLat();
        $this->lat_start = $this->lat_start - ($k * $deltaLat);
        $this->lat_end = $this->lat_end + ($k * $deltaLat);
    }

    /**
     * enlarge longitude range by $k * deltaLongitude
     * @param float $k
     */
    public function increaseLonRange($k) {
        $deltaLon = $this->getDeltaLon();
        $this->lon_start = $this->lon_start - ($k * $deltaLon);
        $this->lon_end = $this->lon_end + ($k * $deltaLon);
    }

    /**
     * enlarge latitude and longitude by $k * delta
     * @param $k
     */
    public function increaseLatLngRange($k) {
        $this->increaseLatRange($k);
        $this->increaseLonRange($k);
    }


    /**
     * @param float $lat
     * @param float $lng
     * @param float $range
     * @return Coords
     */
    public static function createCoordsRangeByLatLng($lat,$lng,$range=0.0) {
        return new self(
            $lat-$range,
            $lat+$range,
            $lng-$range,
            $lng+$range
        );
    }

    /**
     * return average of lat_start and lat_end
     * @return float
     */
    public function getCenterLat() {
        return ($this->lat_start + $this->lat_end) / 2.0;
    }

    /**
     * return average of lon_start and lon_end
     * @return float
     */
    public function getCenterLng() {
        return ($this->lon_start + $this->lon_end) / 2.0;
    }

    /**
     * @return string
     */
    public function toString() {
        return 'latitude: from:' . $this->getLatStart() . ',to:' . $this->getLatEnd() . ', longitude: from: ' . $this->getLonStart() . ',to:' . $this->getLonEnd();
    }

    /**
     * @return mixed
     */
    public function getLatStart()
    {
        return $this->lat_start;
    }

    /**
     * @param mixed $lat_start
     */
    public function setLatStart($lat_start)
    {
        $this->lat_start = $lat_start;
    }

    /**
     * @return float
     */
    public function getLatEnd()
    {
        return $this->lat_end;
    }

    /**
     * @param float $lat_end
     */
    public function setLatEnd($lat_end)
    {
        $this->lat_end = $lat_end;
    }

    /**
     * @return float
     */
    public function getLonStart()
    {
        return $this->lon_start;
    }

    /**
     * @param float $lon_start
     */
    public function setLonStart($lon_start)
    {
        $this->lon_start = $lon_start;
    }

    /**
     * @return float
     */
    public function getLonEnd()
    {
        return $this->lon_end;
    }

    /**
     * @param float $lon_end
     */
    public function setLonEnd($lon_end)
    {
        $this->lon_end = $lon_end;
    }

}