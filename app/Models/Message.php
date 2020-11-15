<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Message extends Model{
    protected $table = 'message';
    public $timestamps = false;
    // 消息的种类
    const TYPE_TEXT = 100; // 文本消息
    const TYPE_IMG = 101; // 图片消息
    const TYPE_PRIVATE_RED = 201; // 微信的私聊红包
    const TYPE_AVERAGE_RED = 202; // 微信的普通群聊均额红包
    const TYPE_LUCKY_RED = 203; // 微信的群聊拼手气红包
    
    public static function getMessages($arrMessageIds){
        return self::whereIn('id', $arrMessageIds)->get();
    }
}