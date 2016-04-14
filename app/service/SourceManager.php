<?php
/**
 * User: Roman
 * Date: 27.08.2015
 * Time: 15:49
 */
namespace App\Service;
use Nette;
use App\Model\Source;

class SourceManager extends BaseService {

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

    public function getById($id) {
        return $this->database->table("source")->where("id",$id)->fetch();
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

    /**
     * save latest_download_data column
     *
     * @param int $id_source
     * @param string $data
     */
    public function saveLatestDownloadDataByIdSource($id_source,$data) {
        $this->database->table('source')->where('id',$id_source)
            ->update(array('latest_download_data'=>$data));
    }

    /**
     * returns latest download data
     * @param int $id_source
     * @return bool|mixed|Nette\Database\Table\IRow
     */
    public function getLatestDownloadDataByIdSource($id_source) {
        $data = $this->database->table('source')->where('id',$id_source)->fetch();
        return $data->latest_download_data;
    }


}
