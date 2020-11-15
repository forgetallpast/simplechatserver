<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ThirdPayOrder extends Model {
    protected $table = 'third_pay_order';
    public $timestamps = false;
    const ONLY_REQUEST = 1; // 发起支付
    const NOTIFY_SUCCESS = 2; // 回调成功
    const TOKEN_EXPIRE = 3600 * 12;
    public static function getByOrderId($orderId){
        return self::where('orderId', $orderId)->first();
    }
    public static function getCount(){
        return self::count();
    }
    public static function getPageData($offset,$limit){
        return self::orderBy('id','desc')->skip($offset)->take($limit)->get();
    }
}