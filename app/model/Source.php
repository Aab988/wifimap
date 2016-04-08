<?php
/**
 * User: Roman
 * Date: 27.08.2015
 * Time: 15:47
 */
namespace App\Model;
use App\Service\GoogleDownload;
use App\Service\WifileaksDownload;
use App\Service\WigleDownload;
use Nette;
class Source extends Nette\Object  {
    /** @var int $id */
    private $id;
    /** @var  string $name */
    private $name;

    /** @var array $colors */
    public static $colors = array(
        WifileaksDownload::ID_SOURCE => "00c0ef",
        WigleDownload::ID_SOURCE => "f39c12",
        GoogleDownload::ID_SOURCE => "5cb85c"
    );

    /**
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}