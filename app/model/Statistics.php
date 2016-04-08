<?php
namespace App\Model;
use Nette;
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:09
 */
class Statistics extends Nette\Object {

    /** @var int $id */
    private $id;
    /** @var Nette\Utils\DateTime $created */
    private $created;
    /** @var  int $total_nets */
    private $total_nets = 0;
    /** @var  int $free_nets */
    private $free_nets = 0;
    /** @var StatisticsSecurity[] $statisticsSecurity */
    private $statisticsSecurity = array();
    /** @var StatisticsSource[] $statisticsSource */
    private $statisticsSource = array();

    /**
     * @param StatisticsSecurity $statisticsSecurity
     */
    public function addStatisticsSecurity($statisticsSecurity) {
        $this->statisticsSecurity[] = $statisticsSecurity;
    }

    /**
     * @param StatisticsSource $statisticsSource
     */
    public function addStatisticsSource($statisticsSource) {
        $this->statisticsSource[] = $statisticsSource;
    }

    /**
     * @return int
     */
    public function getStatisticsSecurityLength() {
        return count($this->statisticsSecurity);
    }

    /**
     * @return int
     */
    public function getStatisticsSourceLength() {
        return count($this->statisticsSource);
    }

    /**
     *
     * @param $id
     * @return StatisticsSecurity
     */
    public function getStatisticsSecurityByIdSecurity($id) {
        foreach($this->statisticsSecurity as $ss) {
            if($ss->getWifiSecurity()->getId() == $id) return $ss;
        }
        return new StatisticsSecurity();
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
     * @return Nette\Utils\DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param Nette\Utils\DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
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

    /**
     * @return StatisticsSecurity[]
     */
    public function getStatisticsSecurity()
    {
        return $this->statisticsSecurity;
    }

    /**
     * @param StatisticsSecurity[] $statisticsSecurity
     */
    public function setStatisticsSecurity($statisticsSecurity)
    {
        $this->statisticsSecurity = $statisticsSecurity;
    }

    /**
     * @return StatisticsSource[]
     */
    public function getStatisticsSource()
    {
        return $this->statisticsSource;
    }

    /**
     * @param StatisticsSource[] $statisticsSource
     */
    public function setStatisticsSource($statisticsSource)
    {
        $this->statisticsSource = $statisticsSource;
    }

}