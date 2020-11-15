<?php
namespace App\Library;
class StringUtil{
    public static function mappedImplode($array, $symbol = '=', $glue = '&') {
        return implode($glue, array_map(
                function($k, $v) use($symbol) {
                    return $k . $symbol . $v;
                },
                array_keys($array),
                array_values($array)
                )
            );
    }
}