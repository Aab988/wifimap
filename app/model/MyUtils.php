<?php
/**
 * User: Roman
 * Date: 09.09.2015
 * Time: 15:18
 */
namespace App\Model;
use App\Presenters\WifiPresenter;
use Nette;
class MyUtils extends Nette\Object {

    public static function isMacAddress($string) {
        return preg_match("^([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})^", urldecode($string));
    }


    public static function macSeparator2Colon($mac) {
        return str_replace("-",":",$mac);
    }


    public static function image2string($img) {
        ob_start();
        imagepng($img);
        $image = ob_get_contents();
        ob_end_clean();
        return $image;
    }


    /**
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

}