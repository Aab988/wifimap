<?php
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:12
 */

namespace App\Model;
use Nette;

class StatisticsSource extends  Nette\Object {
    /** @var int $id */
    private $id;
    /** @var Source $source */
    private $source;
    /** @var int $total_nets */
    private $total_nets;
    /** @var int $free_nets */
    private $free_nets;

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
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param Source $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getTotalNets()
    {
        return $this->total_nets;
    }

    /**
     * @param int $total_nets
     */
    public function setTotalNets($total_nets)
    {
        $this->total_nets = $total_nets;
    }

    /**
     * @return int
     */
    public function getFreeNets()
    {
        return $this->free_nets;
    }

    /**
     * @param int $free_nets
     */
    public function setFreeNets($free_nets)
    {
        $this->free_nets = $free_nets;
    }






}