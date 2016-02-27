<?php
/**
 * User: Roman
 * Date: 22.09.2015
 * Time: 17:10
 */
namespace App\Service;
use App\Model\Log;
use Nette;
class Logger extends Nette\Object {

    const TABLE = 'log';

    /** @var Nette\Database\Context */
    private $database;

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * add log message
     *
     * @param Log $log
     */
    public function addLog(Log $log) {
        $this->database->table(self::TABLE)->insert($log->toArray());
    }

}