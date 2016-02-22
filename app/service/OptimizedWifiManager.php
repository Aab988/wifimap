<?php
/**
 *
 * using PDO (not NetteDB) it is faster than using NetteDB
 * designated for better performance - overlay creation
 *
 * User: Roman
 * Date: 22.02.2016
 * Time: 17:34
 */
namespace App\Service;
use App\Model\MyUtils;
use Nette;

class OptimizedWifiManager extends BaseService {

    const TABLE = 'wifi';


    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @param Nette\Database\Context $database
     */
    public function __construct(Nette\Database\Context $database) {
        parent::__construct($database);
        $this->pdo = $this->database->getConnection()->getPdo();
    }


    public function getNetsByParams($params, $select = array('*'), $limit = null) {
        $SQLselect = $this->buildSelect($select);

        $SQLwhere = array();

        $pdoParams = array();

        foreach($params as $p => $pv) {
            switch($p) {
                case 'coords':
                    $pdoParams[':latStart'] = $pv->getLatStart();
                    $SQLwhere[] = 'latitude > :latStart';
                    $pdoParams[':latEnd'] = $pv->getLatEnd();
                    $SQLwhere[] = 'latitude < :latEnd';
                    $pdoParams[':lonStart'] = $pv->getLonStart();
                    $SQLwhere[] = 'longitude > :lonStart';
                    $pdoParams[':lonEnd'] = $pv->getLonEnd();
                    $SQLwhere[] = 'longitude < :lonEnd';
                    break;
                case 'ssid':
                    $pdoParams[':ssid'] = '%'.$pv.'%';
                    $SQLwhere[] = 'ssid LIKE :ssid';
                    break;
                case 'mac':
                    $pdoParams[':mac'] = '%'.MyUtils::macSeparator2Colon($pv).'%';
                    $SQLwhere[] = 'mac LIKE :mac';
                    break;
                default:
                    $pdoParams[":".$p] = $pv;
                    $SQLwhere[] = $p . " = :$p";
            }
        }

        $where = implode(' AND ',$SQLwhere);

        $sql = "SELECT " . $SQLselect . " FROM " . self::TABLE . " WHERE " . $where;

        $sth = $this->pdo->prepare($sql);
        foreach($pdoParams as $p => $v) {
            $sth->bindValue($p,$v);
        }
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $data;

    }








    /**
     * builds SQL select
     *
     * @param array $select
     * @return string
     */
    private function buildSelect($select = array('*')) {
        $sqlSelect = '*';
        if($select != null) {
            $select2 = array();
            foreach($select as $s) {
                if($s!='') $select2[] = $s;
            }
            $sqlSelect = implode(',',$select2);
        }
        return $sqlSelect;
    }

}