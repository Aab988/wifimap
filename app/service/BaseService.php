<?php
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:57
 */
namespace App\Service;
use Nette;


class BaseService extends Nette\Object {

    /**
     * BASIC CONSTANTS
     */
    // CRON execution - every X minutes, time in minutes - used for calculations
    const CRON_TIME_DOWNLOAD_WIGLE = 30;
    const CRON_TIME_DOWNLOAD_WIGLE_OBSERVATIONS = 30;
    const CRON_TIME_DOWNLOAD_GOOGLE = 5;
    const CRON_TIME_CREATE_STATISTICS = 1440;
    const CRON_TIME_PROCESS_WIGLE_REQUEST = 720;
    const CRON_TIME_PROCESS_GOOGLE_REQUEST = 30;



    /** @var Nette\Database\Context */
    protected $database;
    /** @var Logger */
    protected $logger;

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
        $this->logger = new Logger($this->database);
    }

}