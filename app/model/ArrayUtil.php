<?php
/**
 * User: Roman
 * Date: 23.08.2015
 * Time: 21:59
 */
namespace App\Model;
class ArrayUtil {

    /**
     * return if array is associative
     *
     * @param array $array
     * @return bool
     */
    public static function isAssoc($array) {
        $return = true;
        foreach(array_keys($array) as $a) {
            $return = $return & is_string($a);
        }
        return (bool)$return;
    }

    /**
     * @param array $array
     * @param array $keys
     * @return bool
     */
    public static function arrayHasSomeKey($array,$keys) {
        foreach($keys as $key) {
            if(array_key_exists($key,$array)) return true;
        }
        return false;
    }

}