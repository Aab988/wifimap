<?php
/**
 * User: Roman
 * Date: 27.08.2015
 * Time: 15:49
 */
namespace App\Model;
use Nette;
class SourceManager extends Nette\Object {

    /**
     * @var Nette\Database\Context
     */
    private $database;

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        $this->database = $database;
    }

    /**
     * @return Source[]
     */
    public function getAllSources() {
        $sources = array();
        foreach($this->getAllSourcesFromDB() as $source) {
            $sources[] = new Source($source->id,$source->name);
        }
        return $sources;
    }

    /**
     * return (id => name) array
     * @return array
     */
    public function getAllSourcesAsKeyVal() {
        return $this->database->table("source")->fetchPairs("id","name");

    }

    /**
     * get sources from DB as Row
     * @return array|Nette\Database\Table\IRow[]
     */
    private function getAllSourcesFromDB() {
        return $this->database->table("source")->fetchAll();
    }


}
