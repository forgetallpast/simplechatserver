<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class User extends Model{
    protected $table = 'user';
    public $timestamps = false;
    const TYPE_REGISTER_PLAYER = 101; // 从本系统注册后登录的普通玩家,用户名不允许出现 _
    const TYPE_GUEST_PLAYER = 201; // 游客, 用户名以 g_ 开头
    const TYPE_CUSTOMER_SERVICE = 501; // 客服人员，用户名以 s_ 开头
    // 得到所有用户
    public static function getAllUsers(){
        return self::all();
    }
    
    public static function getById($id){
        return self::find($id);
    }
    public static function getByIds($arrIds){
        return self::whereIn('id', $arrIds)->get();
    }
    public static function getByUsername($username){
        return self::where('username', $username)->first();
    }
    public static function getByTelephone($telephone){
        return self::where('telephone', $telephone)->first();
    }
    public static function getByRefCode($refCode){
        if(empty($refCode)){
            return null;
        }
        return self::where('refCode', $refCode)->first();
    }
    public static function loginByUsername($username,$password){
        return self::where('username',$username)->where('password',$password)->first();
    }
    public static function loginByTelephone($telephone,$password){
        return self::where('telephone',$telephone)->where('password',$password)->first();
    }
    public static function updateUserToken($objUser,$token){
        $objUser->token = $token;
        $objUser->save();
    }
    public static function getByToken($token){
        return self::where('token', $token)->first();
    }
    public function getAuthIdentifier(){
        return 'user|'.$this->id.'|'.$this->username;
    }
    // 取出所有的客服人员
    public static function getAllCustomerService(){
        return self::where('userType', self::TYPE_CUSTOMER_SERVICE)->get();
    }
    // 取出普通用户人数
    public static function getRegisterUsersCount(){
        return self::where('userType', self::TYPE_REGISTER_PLAYER)->count();
    }
    // 取出普通用户分页数据
    public static function getOffsetUsers($offset,$limit){
        return self::where('userType', self::TYPE_REGISTER_PLAYER)
        //->orderBy('id', 'desc')
        ->skip($offset)
        ->take($limit)
        ->get();
    }
}
