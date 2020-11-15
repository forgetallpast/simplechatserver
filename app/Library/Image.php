<?php

namespace App\Library;
use Illuminate\Support\Facades\Storage;
class Image {
    
    public static function toHeaderUrl($header){
        return request()->root() . Storage::url($header . '.png');
    }

    // 重置图片文件高宽
    public static function resizeImage($filename, $dest, $xmax, $ymax, $ratio = true) {
        $ext = explode(".", $filename);
        $ext = $ext[count($ext) - 1];
        if ($ext == "jpg" || $ext == "jpeg")
            $im = imagecreatefromjpeg($filename);
        elseif ($ext == "png")
            $im = imagecreatefrompng($filename);
        elseif ($ext == "gif")
            $im = imagecreatefromgif($filename);
        $x = imagesx($im);
        $y = imagesy($im);
        //if ($x <= $xmax && $y <= $ymax)
        //    return $im;
        if ($ratio) {
            if ($x >= $y) {
                $newx = $xmax;
                $newy = $newx * $y / $x;
            } else {
                $newy = $ymax;
                $newx = $x / $y * $newy;
            }
        } else {
            $newx = $xmax;
            $newy = $ymax;
        }
        $im2 = imagecreatetruecolor($newx, $newy);
        imagecopyresized($im2, $im, 0, 0, 0, 0, floor($newx), floor($newy), $x, $y);
        imagepng($im2, $dest);
    }

}
