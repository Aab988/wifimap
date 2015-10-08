<?php
/**
 * User: Roman
 * Date: 22.09.2015
 * Time: 17:10
 */
namespace App\Service;
use Nette;
class Logger extends Nette\Object {
    /** @var Nette\Database\Context */
    private $database;
    /** @var array */
    private $logs = array();

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * add log message
     *
     * @param string $operation
     * @param string $description
     * @param bool|false $saveImediately
     */
    public function addLog($operation,$description='',$saveImediately = false) {
        $this->logs[] = array("operation"=>$operation,"data"=>$description);
        if($saveImediately) $this->save();
    }


    /**
     * save all logs
     */
    public function save() {
        if(count($this->logs)) {
            $this->database->table("log")->insert($this->logs);
        }
    }

    /**
     * @return array
     */
    public function getLogs() {
        return $this->logs;
    }

}