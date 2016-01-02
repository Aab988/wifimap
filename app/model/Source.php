<?php
/**
 * User: Roman
 * Date: 27.08.2015
 * Time: 15:47
 */
namespace App\Model;
use Nette;
class Source extends Nette\Object  {
    /** @var int $id */
    private $id;
    /** @var  string $name */
    private $name;

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