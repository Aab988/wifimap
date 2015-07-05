<?php
namespace App\Model;
use Nette;
class Wifi extends Nette\Object {
    private $mac = "";
    private $ssid = "";
    private $sec = 0;
    private $latitude = 0;
    private $longtitude = 0;
    private $altitude = 0;
    private $comment = "";
    private $name = "";
    private $type = "";
    private $freenet = "";
    private $paynet = "";
    private $firsttime = "";
    private $lasttime = "";
    private $flags = "";
    private $wep = "";
    private $lastupdt = "";
    private $channel = "";
    private $bcninterval = "";
    private $qos = "";
    
    private $zdroj;
    
    
    public function getMac() {
        return $this->mac;
    }

    public function getSsid() {
        return $this->ssid;
    }

    public function getSec() {
        return $this->sec;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function getLongtitude() {
        return $this->longtitude;
    }

    public function getAltitude() {
        return $this->altitude;
    }

    public function getComment() {
        return $this->comment;
    }

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getFreenet() {
        return $this->freenet;
    }

    public function getPaynet() {
        return $this->paynet;
    }

    public function getFirsttime() {
        return $this->firsttime;
    }

    public function getLasttime() {
        return $this->lasttime;
    }

    public function getFlags() {
        return $this->flags;
    }

    public function getWep() {
        return $this->wep;
    }

    public function getLastupdt() {
        return $this->lastupdt;
    }

    public function getChannel() {
        return $this->channel;
    }

    public function getBcninterval() {
        return $this->bcninterval;
    }

    public function getQos() {
        return $this->qos;
    }

    public function setMac($mac) {
        $this->mac = $mac;
    }

    public function setSsid($ssid) {
        $this->ssid = $ssid;
    }

    public function setSec($sec) {
        $this->sec = $sec;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    public function setLongtitude($longtitude) {
        $this->longtitude = $longtitude;
    }

    public function setAltitude($altitude) {
        $this->altitude = $altitude;
    }

    public function setComment($comment) {
        $this->comment = $comment;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function setFreenet($freenet) {
        $this->freenet = $freenet;
    }

    public function setPaynet($paynet) {
        $this->paynet = $paynet;
    }

    public function setFirsttime($firsttime) {
        $this->firsttime = $firsttime;
    }

    public function setLasttime($lasttime) {
        $this->lasttime = $lasttime;
    }

    public function setFlags($flags) {
        $this->flags = $flags;
    }

    public function setWep($wep) {
        $this->wep = $wep;
    }

    public function setLastupdt($lastupdt) {
        $this->lastupdt = $lastupdt;
    }

    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function setBcninterval($bcninterval) {
        $this->bcninterval = $bcninterval;
    }

    public function setQos($qos) {
        $this->qos = $qos;
    }

    public function getZdroj() {
        return $this->zdroj;
    }

    public function setZdroj($zdroj) {
        $this->zdroj = $zdroj;
    }


    
}
