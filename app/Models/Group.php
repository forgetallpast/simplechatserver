<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Message;
class Group extends Model{
    protected $table = 'group';
    public $timestamps = false;
    // 得到某个用户自己创建的群组， ownerId 为 0 暂时为系统群组，后面可能会改
    public static function getMyOwnerGroups($ownerId){
        return self::where('ownerId', (int)$ownerId)->get();
    }
    // 得到所有的群组
    public static function getAllGroups(){
        return self::all();
    }
    // 得到相关类型的群
    public static function getGroupsByType($type){
        return self::where('groupType', $type)->get();
    }
}