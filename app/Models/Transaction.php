<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

// 资金表，追踪可提余额与冻结余额字段
class Transaction extends Model {
    protected $table = 'transaction';
    public $timestamps = false;
    const THIRD_PAY = 1; // 线上三方支付入款
    const MANUAL_PAY = 2; // 后台手工加钱
    const WITHDRAW = 3; // 提现(冻结)
    const WITHDRAW_REFUND = 4; // 提现被拒绝后返还
    // 记录某个transaction
    public static function insertTransaction($arrData){
        $objTransaction = new Transaction();
        $objTransaction->userId = $arrData['userId'];
        $objTransaction->type = $arrData['type'];
        $objTransaction->changeAmount = $arrData['changeAmount'];
        $objTransaction->afterBalance = $arrData['afterBalance'];
        $objTransaction->attchId = $arrData['attchId']??'';
        $objTransaction->save();
    }
    // 倒序取某个用户的分页数据
    public static function getUserPageData($userId,$offset,$limit){
        return self::where('userId',$userId)->skip($offset)->take($limit)
        ->orderBy('id','desc')->get();
    }
    // 倒序取某个用户某些类型的分页数据
    public static function getUserTypePageData($userId,$offset,$limit, $arrTypes){
        return self::where('userId',$userId)->whereIn('type',$arrTypes)
        ->skip($offset)->take($limit)
        ->orderBy('id','desc')->get();
    }
    // 交易类型的文字说明
    public static function typeToString($type,$lang='zh'){
        $arrText = [
            self::THIRD_PAY => '三方支付',
            self::MANUAL_PAY => '手动加款',
            self::WITHDRAW => '提现',
            self::WITHDRAW_REFUND => '提现退还',
        ];
        return $arrText[$type];
    }
}