<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Withdraw extends Model{
    protected $table = 'withdraw';
    public $timestamps = false;
    const APPLY = 1; // 申请出款状态
    const GIVEN = 2; // 确认已出款
    const DENY = 3; // 确认已拒绝
    const PROCESSING = 4; // 处理中，订单锁定
    public static function getById($orderId){
        return self::find($orderId);
    }
    public static function getCount(){
        return self::count();
    }
    public static function getPageData($offset,$limit){
        return self::orderBy('id','desc')->skip($offset)->take($limit)->get();
    }
}