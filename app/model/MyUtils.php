<?php
/**
 * User: Roman
 * Date: 09.09.2015
 * Time: 15:18
 */
namespace App\Model;
use Nette;

class MyUtils extends Nette\Object {

    /**
     * return if string is mac address
     *
     * @param string $string
     * @return int
     */
    public static function isMacAddress($string) {
        return preg_match("^([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})^", urldecode($string));
    }

    /**
     * replace - to : in mac address
     *
     * @param string $mac
     * @return mixed
     */
    public static function macSeparator2Colon($mac) {
        return str_replace("-",":",$mac);
    }

    /**
     * generate key for cache save
     *
     * @param string $mode
     * @param Coords $coords
     * @param array[] $params
     * @return string
     */
    public static function generateCacheKey($mode,$coords,$zoom,$params = array()) {
       $key = $mode
           . ':' . $coords->getLatStart()
           . ':' . $coords->getLatEnd()
           . ':' . $coords->getLonStart()
           . ':' . $coords->getLonEnd()
           . ':' . $zoom;
        if(count($params)>0) {
            $key .= "::";
            foreach($params as $k=>$v) $key.="~".$k."=".$v;
        }
        return $key;
    }

    /**
     * set script variables if server is not in safe_mode
     *
     * @param int $max_execution_time number of seconds
     * @param string $max_memory fe: '256M'
     *
     */
    public static function setIni($max_execution_time = null,$max_memory = null) {
        if(!ini_get('safe_mode')) {
            if($max_execution_time) set_time_limit($max_execution_time);
            if($max_memory) ini_set('memory_limit',$max_memory);
        }
    }

    public static function image2string($img) {
                ob_start();
                imagepng($img);
                $image = ob_get_contents();
               ob_end_clean();
               return $image;
    }
}