<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageHistory extends Model{
    protected $table = 'message_history';
    public $timestamps = false;
    // 拿历史聊天记录，注意是倒序
    // maxMessageId: 最大的id，不含
    public static function getHistoryMessages($arrParams){
        $ormHistory = self::where('selfId',$arrParams['selfId'])
               ->where('chatId', $arrParams['chatId']);
        if(!empty($arrParams['maxMessageId'])){
            $ormHistory = $ormHistory->where('messageId', '<', $arrParams['maxMessageId']);
        }
        return $ormHistory->orderBy('messageId', 'desc')
               ->take($arrParams['limit'])
               ->get();
    }
    // 查查信息，校验一下
    public static function getUniqueMessage($selfId,$messageId){
        return self::where('selfId',$selfId)->where('messageId',$messageId)->first();
    }
}
