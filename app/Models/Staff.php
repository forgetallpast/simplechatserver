<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model {
    protected $table = 'staff';
    public $timestamps = false;
    const ONJOB = 1; // 在职
    const RESIGNED = 2; // 离职
    const TOKEN_EXPIRE = 3600 * 12;
    // md5(concat('pre123', $password))
    // 通过用户名密码拿到员工信息
    public static function login($username,$password){
        Log::info('username '.$username.' password '.$password. ' pre '.config('app.password_pre'));
        $realPassword = md5(md5($password).config('app.password_pre'));
        Log::info('realpassword '.$realPassword);
        $objStaff = self::where('username',$username)
                ->where('password',$realPassword)
                ->where('status', self::ONJOB)
                ->first();
        if(empty($objStaff)){
            return false;
        }else{
            $token = (string) Str::uuid();
            $objStaff->token = $token;
            $objStaff->save();
            return $objStaff;
        }
    }
    
    public function getAuthIdentifier(){
        return 'staff|'.$this->id.'|'.$this->username;
    }
}