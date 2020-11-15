<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Promo extends Model{
    protected $table = 'promo';
    public $timestamps = false;
    // 优惠状态
    const STATUS_ACTIVE = 1; // 生效中
    const STATUS_INACTIVE = 2; // 失效
    public static function getAllDesc(){
        return self::orderBy('id','desc')->get();
    }
    // 根据优惠码取优惠
    public static function getByPromoCode($promoCode){
        return self::where('promoCode', $promoCode)->first();
    }
    // 只取有效的优惠码
    public static function getAllActiveDesc(){
        return self::where('status', self::STATUS_ACTIVE)->orderBy('id','desc')->get();
    }
}