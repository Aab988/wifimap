<?php
/**
 * User: Roman
 * Date: 20.08.2015
 * Time: 21:23
 */
namespace App\Model;
class Color {

    public $r, $g, $b;

    public static $colors = array(
        '00c0ef','00a65a','f39c12','dd4b39','337ab7','5cb85c','5bc0de','f0ad4e','d9534f'
    );

    public function __construct($r = 0,$g = 0,$b = 0) {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }


    public static function GetRandomColor($color = null, $shade = false) {

        if(!$color) {
            $rand = (int)rand(0,count(self::$colors)-1);
            $color = isset(self::$colors[$rand]) ? self::$colors[$rand] : "dddddd";
            unset(self::$colors[$rand]);
            if(empty(self::$colors)) {
                self::$colors = array('00c0ef','00a65a','f39c12','dd4b39','337ab7','5cb85c','5bc0de','f0ad4e','d9534f');
            }
        }
        else {
            $color = ltrim($color,"#");
        }

        if($shade) {
            $rx = $color[0].$color[1];
            $gx = $color[2].$color[3];
            $bx = $color[4].$color[5];
            $color = sprintf('%02X%02X%02X', hexdec($rx) / 2, hexdec($gx) / 2, hexdec($bx) / 2);
        }

        return "#".$color;
    }



}