<?php

namespace App\Presenters;

use App\Service\StatisticsManager;
use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class StatisticsPresenter extends BasePresenter {

	/**
	 * @var StatisticsManager
	 * @inject
	 */
	public $statisticsManager;


	public $database;

	public function __construct(Nette\Database\Context $database) {

		$this->database = $database;
	}



	public function renderDefault()
	{
		$latestStat = $this->statisticsManager->getLatestStatistics();
		$allStats = $this->statisticsManager->getAllStatistics();


		//dump($latestStat);
		//dump($allStats);

		$this->template->actualStatistics = $this->statisticsManager->getLatestStatistics();
		$this->template->secondLatestStatistics = ($allStats[count($allStats)-2])?$allStats[count($allStats)-2]:null;

		$this->template->allStatistics = $allStats;

		// vzit statistiku nejnovejsi
		// vzit všechny starší statistiky

		// vezmu vsechny statistiky
		/*$statistics = $this->database->table("statistics")->order("created DESC")->fetchAll();
		$actualStatistics = null;
		if($statistics) {
			$stat_keys = array_keys($statistics);
			$keyfirst = $stat_keys[0];
			$actualStatistics = $statistics[$keyfirst];
			$this->template->actualStatistics = $actualStatistics;
			if(count($stat_keys>=2)) {
				$second = $statistics[$stat_keys[1]];
				$this->template->secondLatestStatistics = $second;
			}
		}

		$this->template->allStatistics = array_reverse($statistics,true);
		*/

		// vzit nejnovejsi statistiky zdroju
		/*$source_statistics_actual = $this->database
			->query("select ss.total_nets,ss.free_nets,s.name
 						from statistics_source ss join source s on (ss.id_source = s.id)
 						where id_statistics=?",$actualStatistics->id)
			->fetchAll();

		// vzit historii statistik zdroju
		$source_statistics_all = $this->database->query("select st.created,ss.total_nets,ss.free_nets,s.name from statistics_source ss join statistics st on (ss.id_statistics = st.id) join source s on (ss.id_source = s.id)")->fetchAll();

		$this->template->sourceStatisticsActual = $source_statistics_actual;
		$this->template->sourceStatisticsAll = $source_statistics_all;

		// vzit nejnovejsi statistiky zabezpeceni
		// vzit historii statistik zabezpeceni
		*/





		//$this->template->wifi = $this->database->table("wifi")->where("id_zdroj=2")->order("rand()");
	}



	public function renderCreateStatistics() {
		$exists = $this->database->table("statistics")->where("created", date("Y-m-d"))->fetch();
		if($exists) {
			echo "statistika již existuje";
			$this->terminate();
		}

		// vytvorit zakladni statistiku a zachovat si jeji ID
		$total_nets = $this->database->table("wifi")->select("count(id) AS pocet")->fetch();
		$free_nets = $this->database->table("wifi")->select("count(id) AS pocet")->where("sec", 1)->fetch();

		/*dump($total_nets);
		dump($free_nets);*/

		$id = $this->database->table("statistics")->insert(
			array("created"=>new Nette\Utils\DateTime(),
				"total_nets"=>$total_nets->pocet, "free_nets"=>$free_nets->pocet));
		//dump($id);

		// vytvorit statistiky zabezpeceni s nastavenym ID_statistics timto
		$ssec = $this->database->query("select ws.id,count(w.id) AS pocet from wifi w join wifi_security ws on (w.sec = ws.id) group by ws.id")->fetchAll();
		//dump($ssec);
		foreach($ssec as $s) {
			$this->database->table("statistics_security")->insert(array(
				"id_statistics"=>$id,
				"id_wifi_security"=>$s->id,
				"total_nets" => $s->pocet
			));
		}

		// vytvoreni statistiky zdroju s nastavenym ID_statistics timto
		$ssource = $this->database->query("select s.id,count(w.id) as pocet from wifi w join source s on (w.id_source = s.id) group by s.id")->fetchAll();
		//dump($ssource);

		foreach($ssource as $s) {
			$count = $this->database->table("wifi")->select("count(id) AS pocet")->where("sec", 1)->where("id_source",$s->id)->fetch();
			$this->database->table("statistics_source")->insert(array(
				"id_statistics"=>$id,
				"id_source" => $s->id,
				"total_nets"=>$s->pocet,
				"free_nets"=>$count->pocet
			));
		}


		// statistika vytvorena
		echo "Statistika byla vytvořena";

		$this->terminate();

	}



}
