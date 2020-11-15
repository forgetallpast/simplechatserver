<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class UserBankcard extends Model {
    protected $table = 'user_bankcard';
    public $timestamps = false;
    const STATUS_INUSE = 1;
    const STATUS_DELETED = 2;
    // 根据id得到银行卡
    public static function getBankcardById($id){
        return self::find($id);
    }
    // 根据id数组得到银行卡
    public static function getByIds($arrIds){
        return self::whereIn('id', $arrIds)->get();
    }
    // 得到单个用户所有的银行卡信息
    public static function getUserBankcards($userId){
        return self::where('userId', $userId)->get();
    }
    // 得到单个用户有效的银行卡信息
    public static function getActiveUserBankcards($userId){
        return self::where('userId', $userId)->where('status', self::STATUS_INUSE)->get();
    }
}