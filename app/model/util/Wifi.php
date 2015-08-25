<?php
namespace App\Model;
use Nette;

class Wifi extends Nette\Object {
    /** @var string  */
    private $mac = "";
    /** @var string  */
    private $ssid = "";
    /** @var int  */
    private $sec = 0;
    /** @var float  */
    private $latitude = .0;
    /** @var float  */
    private $longitude = .0;
    /** @var float  */
    private $altitude = .0;
    /** @var string  */
    private $comment = "";
    /** @var string  */
    private $name = "";
    /** @var string  */
    private $type = "";
    /** @var string  */
    private $freenet = "";
    /** @var string  */
    private $paynet = "";
    /** @var string  */
    private $firsttime = "";
    /** @var string  */
    private $lasttime = "";
    /** @var string  */
    private $flags = "";
    /** @var string  */
    private $wep = "";
    /** @var string  */
    private $lastupdt = "";
    /** @var string|null  */
    private $channel = null;
    /** @var string  */
    private $bcninterval = "";
    /** @var string  */
    private $qos = "";
    /** @var int */
    private $source;

    /**
     * @return string
     */
    public function getMac() {
        return $this->mac;
    }

    /**
     * @return string
     */
    public function getSsid() {
        return $this->ssid;
    }

    /**
     * @return int
     */
    public function getSec() {
        return $this->sec;
    }

    /**
     * @return float
     */
    public function getLatitude() {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function getLongitude() {
        return $this->longitude;
    }

    /**
     * @return float
     */
    public function getAltitude() {
        return $this->altitude;
    }

    /**
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFreenet() {
        return $this->freenet;
    }

    /**
     * @return string
     */
    public function getPaynet() {
        return $this->paynet;
    }

    /**
     * @return string
     */
    public function getFirsttime() {
        return $this->firsttime;
    }

    /**
     * @return string
     */
    public function getLasttime() {
        return $this->lasttime;
    }

    /**
     * @return string
     */
    public function getFlags() {
        return $this->flags;
    }

    /**
     * @return string
     */
    public function getWep() {
        return $this->wep;
    }

    /**
     * @return string
     */
    public function getLastupdt() {
        return $this->lastupdt;
    }

    /**
     * @return null|string
     */
    public function getChannel() {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getBcninterval() {
        return $this->bcninterval;
    }

    /**
     * @return string
     */
    public function getQos() {
        return $this->qos;
    }

    /**
     * @param string $mac
     */
    public function setMac($mac) {
        $this->mac = $mac;
    }

    /**
     * @param string $ssid
     */
    public function setSsid($ssid) {
        $this->ssid = $ssid;
    }

    /**
     * @param int $sec
     */
    public function setSec($sec) {
        $this->sec = $sec;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    /**
     * @param float $altitude
     */
    public function setAltitude($altitude) {
        $this->altitude = $altitude;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment) {
        $this->comment = $comment;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @param string $freenet
     */
    public function setFreenet($freenet) {
        $this->freenet = $freenet;
    }

    /**
     * @param string $paynet
     */
    public function setPaynet($paynet) {
        $this->paynet = $paynet;
    }

    /**
     * @param string $firsttime
     */
    public function setFirsttime($firsttime) {
        $this->firsttime = $firsttime;
    }

    /**
     * @param string $lasttime
     */
    public function setLasttime($lasttime) {
        $this->lasttime = $lasttime;
    }

    /**
     * @param string $flags
     */
    public function setFlags($flags) {
        $this->flags = $flags;
    }

    /**
     * @param string $wep
     */
    public function setWep($wep) {
        $this->wep = $wep;
    }

    /**
     * @param string $lastupdt
     */
    public function setLastupdt($lastupdt) {
        $this->lastupdt = $lastupdt;
    }

    /**
     * @param null|int $channel
     */
    public function setChannel($channel) {
        $this->channel = $channel;
    }

    /**
     * @param string $bcninterval
     */
    public function setBcninterval($bcninterval) {
        $this->bcninterval = $bcninterval;
    }

    /**
     * @param string $qos
     */
    public function setQos($qos) {
        $this->qos = $qos;
    }

    /**
     * @return int
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @param int $source
     */
    public function setSource($source) {
        $this->source = $source;
    }

    /**
     * synchronize security values between wigle and wifileaks
     */
    public function synchronizeSecurity() {
        // security 0 - open, 1- WEP, 2- WPA1, 3-WPA2,4-other
        if($this->source == WifileaksDownload::ID_SOURCE) {
            switch(intval($this->sec)) {
                case 0:
                    $this->freenet = 'Y'; $this->paynet = '?'; $this->wep = 'N';
                    break;
                case 1:
                    $this->freenet = '?'; $this->paynet = '?'; $this->wep = 'Y';
                    break;
                case 2:
                    $this->freenet = '?'; $this->paynet = '?'; $this->wep = 'W';
                    break;
                case 3:
                    $this->freenet = '?'; $this->paynet = '?'; $this->wep = '2';
                    break;
                case 4:
                    $this->freenet = '?'; $this->paynet = '?'; $this->wep = '4';
                    break;
            }

        }
        if($this->source == WigleDownload::ID_SOURCE) {
            switch($this->wep) {
                case '?':
                    $this->sec = ($this->freenet == 'Y') ? 0 : 4;
                    break;
                case 'Y':
                    $this->sec = 1;break;
                case 'N':
                    if($this->freenet != 'N' && $this->paynet != 'Y') $this->freenet = 'Y';
                    $this->sec = 0;break;
                case 'W':
                    $this->sec = 2;break;
                case '2':
                    $this->sec = 3;break;
                default:
                    $this->sec = 4;break;
            }
        }
    }


    
}
