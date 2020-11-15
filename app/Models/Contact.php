<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Contact extends Model{
    protected $table = 'contact';
    public $timestamps = false;
    // 要特别注意到，由于没有id分配器，所以，群聊，私聊等，可能出现主体为同一个id的情况!
    // 消息的大类分类，是私聊，群聊，还是系统消息
    const CATEGORY_PRIVATE = 1; // 私聊
    const CATEGORY_GROUP = 2; // 群组
    // TODO: const CATEGORY_SERVICE_ACCOUNT = 3; //服务号发送消息
    // 取得所有私聊的联系人
    public static function getPrivateContacts($userId){
        return self::where('selfId', $userId)->where('category', self::CATEGORY_PRIVATE)->get();
    }
    // 不需要排序的通讯录联系人
    public static function getUserContacts($userId){
        return self::where('selfId', $userId)->orderBy('id', 'desc')->get();
    }
    // 按最后消息排好顺序的联系人
    public static function getOrderedUserContacts($userId){
        return self::where('selfId', $userId)->orderBy('lastMessageId', 'desc')->orderBy('id', 'desc')->get();
    }
    public static function getById($chatId){
        return self::find($chatId);
    }
    // 用于私聊来找会话id
    public static function getByTwoIds($selfId,$otherId){
        return self::where('selfId',$selfId)->where('otherId',$otherId)
        ->where('category', self::CATEGORY_PRIVATE)->first();
    }
    // 群聊中除自己外所有的id
    public static function getOtherGroupContacts($selfId, $otherId){
        return self::where('otherId', $otherId)->where('selfId','<>', $selfId)->where('category',self::CATEGORY_GROUP)->get();
    }
    // 群聊中所有的id
    public static function getGroupContacts($otherId){
        return self::where('otherId', $otherId)->where('category',self::CATEGORY_GROUP)->get();
    }
    // 查询两个id是否已经是好友
    public static function isFriend($uid1, $uid2){
        return !empty(self::where('selfId', $uid1)->where('otherId',$uid2)
            ->where('category', self::CATEGORY_PRIVATE)->first());
    }
}