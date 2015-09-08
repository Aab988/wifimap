<?php
namespace App\Service;
use App\Model\Source;
use App\Model\Statistics;
use App\Model\StatisticsSecurity;
use App\Model\StatisticsSource;
use App\Model\WifiSecurity;
use Nette;

/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:33
 */
class StatisticsManager extends BaseService {


    /**
     * @return Statistics[]
     */
    public function getAllStatistics()
    {
        $allStats = array();
        $all = $this->database->table("statistics")->select("id")->order("created ASC")->fetchAll();
        foreach ($all as $a) {
            $allStats[] = $this->getStatisticsById($a->id);
        }
        return $allStats;
    }

    /**
     * return newest statistics
     *
     * @return Statistics
     */
    public function getLatestStatistics() {
        $latestStatistics = new Statistics();

        $latest = $this->database->table("statistics")->order("created DESC")->limit(1)->fetch();

        $latestStatistics->setId($latest->id);
        $latestStatistics->setCreated($latest->created);
        $latestStatistics->setTotalNets($latest->total_nets);
        $latestStatistics->setFreeNets($latest->free_nets);

        $latestStatistics->setStatisticsSource($this->getStatisticsSourceByIdStatistics($latest->id));
        $latestStatistics->setStatisticsSecurity($this->getStatisticsSecurityByIdStatistics($latest->id));

        return $latestStatistics;
    }


    /**
     * @param int $sid
     * @return Statistics
     */
    public function getStatisticsById($sid) {
        $stat = new Statistics();
        $s = $this->database->table("statistics")->where("id",$sid)->fetch();
        $stat->setId($s->id);
        $stat->setCreated($s->created);
        $stat->setTotalNets($s->total_nets);
        $stat->setFreeNets($s->free_nets);
        $stat->setStatisticsSource($this->getStatisticsSourceByIdStatistics($s->id));
        $stat->setStatisticsSecurity($this->getStatisticsSecurityByIdStatistics($s->id));

        return $stat;
    }


    /**
     * @param int $sid
     * @return StatisticsSecurity[]
     */
    public function getStatisticsSecurityByIdStatistics($sid) {
        $stat_sec = array();
        $latest_security_statistics = $this->database->query("select ss.id,ss.total_nets,ws.id AS 'ws_id',ws.label AS 'ws_label' from statistics_security ss join wifi_security ws on (ss.id_wifi_security = ws.id) where id_statistics=?",$sid)->fetchAll();
        foreach($latest_security_statistics as $lss) {
            $ss = new StatisticsSecurity();
            $ss->setId($lss->id);
            $ss->setTotalNets($lss->total_nets);
            $ws = new WifiSecurity($lss->ws_id,$lss->ws_label);
            $ss->setWifiSecurity($ws);
            $stat_sec[] = $ss;
        }
        return $stat_sec;
    }


    /**
     * @param int $sid
     * @return StatisticsSource[]
     */
    public function getStatisticsSourceByIdStatistics($sid) {
        $stat_source = array();
        $latest_source_statistics = $this->database->query("select ss.id,ss.total_nets,ss.free_nets,s.name AS 'sourcename',s.id AS 'sourceid' from statistics_source ss join source s on (ss.id_source = s.id) where id_statistics=?",$sid)->fetchAll();
        foreach($latest_source_statistics as $lss) {
            $ss = new StatisticsSource();
            $ss->setId($lss->id);
            $ss->setSource(new Source($lss->sourceid,$lss->sourcename));
            $ss->setTotalNets($lss->total_nets);
            $ss->setFreeNets($lss->free_nets);
            $stat_source[] = $ss;
        }
        return $stat_source;
    }

}