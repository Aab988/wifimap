<?php
namespace App\Model;
use Nette;
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:14
 */
class WifiSecurity extends Nette\Object {

    /** @var int id */
    private $id;
    /** @var string $label */
    private $label;

    /** @var array $colors */
    public static $colors = array(
        1 => "dd4b39",
        2 => "f39c12",
        3 => "ffc700",
        4 => "00a65a",
        5 => "dddddd",
    );

    /**
     * @param int $id
     * @param string $label
     */
    public function __construct($id=0,$label="N/A") {
        $this->id = $id;
        $this->label = $label;
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