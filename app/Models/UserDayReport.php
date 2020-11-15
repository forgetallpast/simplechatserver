<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class UserDayReport extends Model {
    protected $table = 'user_day_report';
    public $timestamps = false;
    public static function getNonZeroCount($day,$field){
        return self::where('day', $day)->where($field,'>=',0)->count();
    }
    public static function getNonZeroPageData($day, $offset, $limit, $field){
        return self::where('day', $day)
        ->where($field,'>=', 0)
        ->orderBy('id','desc')
        ->skip($offset)
        ->take($limit)
        ->get();
    }
}