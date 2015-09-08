<?php
/**
 * User: Roman
 * Date: 08.09.2015
 * Time: 16:57
 */
namespace App\Service;
use Nette;


class BaseService extends Nette\Object {

    protected $database;

    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

}