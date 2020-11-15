<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApiUser extends Model{
    protected $table = 'api_user';
    public $timestamps = false;
    public static function getByUserIdApiName($userId,$apiName){
        return self::where('userId',$userId)->where('apiName',$apiName)->first();
    }
}