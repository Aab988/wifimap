<?php
/**
 * User: Roman
 * Date: 19.12.2015
 * Time: 13:48
 */

namespace App\Model;
use Nette\Object;

class DownloadImport extends Object
{

    const ADDED_WIGLE = 1;
    const DOWNLOADED_WIGLE = 2;
    const ADDED_GOOGLE = 3;
    const DOWNLOADED_GOOGLE = 4;

    /** @var int $id */
    private $id;
    /** @var string $mac */
    private $mac;
    /** @var int $state */
    private $state;
    /** @var int $id_wigle_aps */
    private $id_wigle_aps;
    /** @var int $id_google_request */
    private $id_google_request;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * @param string $mac
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getIdWigleAps()
    {
        return $this->id_wigle_aps;
    }

    /**
     * @param int $id_wigle_aps
     */
    public function setIdWigleAps($id_wigle_aps)
    {
        $this->id_wigle_aps = $id_wigle_aps;
    }

    /**
     * @return int
     */
    public function getIdGoogleRequest()
    {
        return $this->id_google_request;
    }

    /**
     * @param int $id_google_request
     */
    public function setIdGoogleRequest($id_google_request)
    {
        $this->id_google_request = $id_google_request;
    }


    public function toArray() {
        return array(
            'mac' => $this->mac,
            'state' => $this->state,
            'id_wigle_aps' => $this->id_wigle_aps,
            'id_google_request' => $this->id_google_request
        );
    }



}