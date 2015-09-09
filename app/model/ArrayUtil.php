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

}