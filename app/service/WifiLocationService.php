<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 21.10.2015
 * Time: 20:07
 */
namespace App\Service;
use App\Model\Coords;
use App\Model\Wifi;

class WifiLocationService extends BaseService {

    /** @var WifiManager */
    private $wifiManager;

    const RADIUS = 0.003;

    /**
     * @param Wifi $wifi
     * @return Wifi[]
     */
    public function getLocation(Wifi $wifi) {

        $this->wifiManager = new WifiManager($this->database);

        $lat = $wifi->getLatitude();
        $lon = $wifi->getLongitude();


        $lat1 = doubleval($lat) - self::RADIUS;
        $lat2 = doubleval($lat) + self::RADIUS;
        $lon1 = doubleval($lon) - self::RADIUS/2;
        $lon2 = doubleval($lon) + self::RADIUS/2;

        $coords = new Coords($lat1,$lat2,$lon1,$lon2);

        $nets = $this->wifiManager->getNetsModeSearch($coords, array('mac'=>$wifi->getMac()));

        return $nets;

    }









}