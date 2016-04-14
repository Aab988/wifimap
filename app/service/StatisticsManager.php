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
    public function getAllStatistics() {
        $count = $this->database->table("statistics")->count("id");
        $between = round($count/5);

        $last = $this->database->table("statistics")->select("max(id) AS max_id")->fetch();
        $first = $this->database->table("statistics")->select("min(id) AS min_id")->fetch();

        if($last && $first) {
            $_25 = $first["min_id"] + $between;
            $_75 = $last["max_id"] - $between;

            $avg = $this->database->table("statistics")->select("round(avg(id)) AS avg_id")
                ->fetch();

            $avgF = $this->database->table("statistics")->select("id")
                ->where("id",$_25)
                ->fetch();
            $avgL = $this->database->table("statistics")->select("id")
                ->where("id",$_75)
                ->fetch();

            $all = array();
            if($first) $all[] = (object) array("id" => $first["min_id"]);
            if($avgF) $all[] = (object) array("id" => $avgF["id"]);
            if($avg) $all[] = (object) array("id" => $avg["avg_id"]);
            if($avgL) $all[] = (object) array("id" => $avgL["id"]);
            if($last) $all[] = (object) array("id" => $last["max_id"]);
        }
        else {
            $all = $this->database->table("statistics")->select("id")->order("created ASC")->fetchAll();
        }

        $allStats = array();

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
     * @return Statistics
     */
    public function getSecondLatestStatistics() {
        $latest = $this->getLatestStatistics();
        $latestId = $latest->getId();
        $sl = $this->database->table("statistics")
            ->where("id != ?",$latestId)
            ->order("id DESC")
            ->limit(1)
            ->fetch();
        return $this->getStatisticsById($sl["id"]);
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
            $stat_sec[$lss->ws_id] = $ss;
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
            $stat_source[$lss->sourceid] = $ss;
        }
        return $stat_source;
    }

    /**
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    public function getCurrentStatistics() {
        return $this->database->table("statistics")->where("created", date("Y-m-d"))->fetch();
    }

    /**
     * creates new statistics
     */
    public function createStatistics() {
        $total_nets = $this->database->table("wifi")->select("count(id) AS pocet")->fetch();
        $free_nets = $this->database->table("wifi")->select("count(id) AS pocet")->where("sec", 1)->fetch();

        $id = $this->database->table("statistics")->insert(array(
            "created"=>new Nette\Utils\DateTime(),
            "total_nets"=>$total_nets->pocet,
            "free_nets"=>$free_nets->pocet)
        )->getPrimary(true);

        // create security statistics
        $ssec = $this->database->query("SELECT ws.id,count(w.id) AS pocet FROM wifi w JOIN wifi_security ws ON (w.sec = ws.id) GROUP BY ws.id")->fetchAll();
        foreach($ssec as $s) {
            $this->database->table("statistics_security")->insert(array(
                "id_statistics"=>$id,
                "id_wifi_security"=>$s->id,
                "total_nets" => $s->pocet
            ));
        }

        // create source statistics
        $ssource = $this->database->query("SELECT s.id,count(w.id) AS pocet FROM wifi w JOIN source s ON (w.id_source = s.id) GROUP BY s.id")->fetchAll();
        foreach($ssource as $s) {
            $count = $this->database->table("wifi")->select("count(id) AS pocet")->where("sec", 1)->where("id_source",$s->id)->fetch();
            $this->database->table("statistics_source")->insert(array(
                "id_statistics"=>$id,
                "id_source" => $s->id,
                "total_nets"=>$s->pocet,
                "free_nets"=>$count->pocet
            ));
        }
    }


}