<?php
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:57
 */
namespace App\Service;
use Nette;


class BaseService extends Nette\Object {

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