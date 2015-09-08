<?php
namespace App\Model;
use Nette;
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:14
 */
class WifiSecurity extends Nette\Object {


    /**
     * @param int $id
     * @param string $label
     */
    public function __construct($id=0,$label="") {
        $this->id = $id;
        $this->label = $label;
    }

    /** @var int id */
    private $id;
    /** @var string $label */
    private $label;

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
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }


}