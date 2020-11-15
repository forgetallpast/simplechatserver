<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GroupMember extends Model{
    protected $table = 'group_member';
    public $timestamps = false;
    // 是否已经加入群组
    public static function isJoinGroup($userId,$groupId){
        return !empty(self::where('groupId',$groupId)->where('userId',$userId)->first());
    }
    // 得到某个群组所有的会员列表
    public static function getMembers($groupId){
        return self::where('groupId', (int)$groupId)->get();
    }
}