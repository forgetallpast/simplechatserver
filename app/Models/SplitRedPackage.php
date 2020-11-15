<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;
class SplitRedPackage extends Model{
    const STILL_AVAILABLE = 1; // 发出未领取
    const ALREADY_PICKED = 2; // 已经被领取
    const EXPIRED_REFUNDED = 3; //过期已退回
    const ALREADY_SAFE = 4; // 已经被领取且不需要赔付
    const ALREADY_PAYOUT = 5; // 已经被领取且已经赔付
    protected $table = 'split_red_package';
    public $timestamps = false;
    public static function getPackageInfo($redPackageId){
        return self::where('redPackageId',$redPackageId)->get();
    }
    public static function getPickedPackages($redPackageId){
        return self::where('redPackageId',$redPackageId)->where('pickUserId','<>',0)->where('status', self::ALREADY_PICKED)->get();
    }
}
