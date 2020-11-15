<?php

namespace App\Service;

use App\Models\Contact;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use App\Models\MessageHistory;
use App\Models\User;
use GuzzleHttp\Client;
use App\Library\Image;

class MessageService {
    // objUser 向 objContact 发送一条消息
    public static function sendMessage($objContact, $objUser, $messageType, $info) {
        $category = $objContact->category;
        // 先从发送者角度存储消息，谁先存进数据库就是谁先发的消息
        $objMessage = new Message();
        $objMessage->chatId = $objContact->id;
        $objMessage->senderId = $objUser->id;
        $objMessage->senderNickname = $objUser->nickname;
        $objMessage->senderHeader = $objUser->header;
        $objMessage->messageType = $messageType;
        $objMessage->info = $info;
        $objMessage->sendTime = date('Y-m-d H:i:s');
        if (!$objMessage->save()) {
            Log::error('save message fail ' . $objContact->id . ' ' . $info);
            return false;
        }
        // 给自己改一下最后的记录
        /*
        $objContact->unreadCount = 0;
        $objContact->lastMessageId = $objMessage->id;
        $objContact->lastMessageTime = $objMessage->sendTime;
        $objContact->lastMessageSenderNickname = $objUser->nickname;
        $objContact->lastMessageType = $messageType;
        $objContact->lastMessageInfo = $info;
        $objContact->save();
        */
        // 给自己的记录表存上这条消息
        /*
        $objMyHistory = new MessageHistory();
        $objMyHistory->selfId = $objUser->id;
        $objMyHistory->chatId = $objContact->id;
        $objMyHistory->messageId = $objMessage->id;
        if (!$objMyHistory->save()) {
            Log::error('save my history fail ' . $objContact->id . ' ' . $objMessage->id);
        }
        */
        // 发送给所有的人，包括自己
        // 找到所有人此次会话的记录，存上去
        $arrOtherChats = [];
        if ($category == Contact::CATEGORY_PRIVATE) {
            // 私聊消息
            $objOtherChat = Contact::getByTwoIds($objContact->otherId, $objContact->selfId);
            if (empty($objOtherChat)) {
                Log::error('no other chat ' . $objContact->otherId . ' ' . $objContact->selfId);
            } else {
                $arrOtherChats = [$objContact, $objOtherChat];
            }
        } else {
            // 群聊消息
            $arrOtherChats = Contact::getGroupContacts($objContact->otherId);
        }
        // 给别的会话者改最后的记录
        foreach ($arrOtherChats as $objOtherChat) {
            // 先加一下未读记录
            $objOtherChat->increment('unreadCount', 1);
            $objOtherChat->lastMessageId = $objMessage->id;
            $objOtherChat->lastMessageTime = $objMessage->sendTime;
            $objOtherChat->lastMessageSenderNickname = $objUser->nickname;
            $objOtherChat->lastMessageType = $messageType;
            $objOtherChat->lastMessageInfo = $info;
            $objOtherChat->save();
            // 然后改历史记录      
            $objOtherHistory = new MessageHistory();
            $objOtherHistory->selfId = $objOtherChat->selfId;
            $objOtherHistory->chatId = $objOtherChat->id;
            $objOtherHistory->messageId = $objMessage->id;
            if (!$objOtherHistory->save()) {
                Log::error('save other history fail ' . $objOtherChat->id . ' ' . $objMessage->id);
            }
        }
        // 给所有人，包括自己，开始 socket 分发
        $arrUserIds = [];
        $arrOtherUsers = [];
        // 私聊就是发给对方了
        if ($category == Contact::CATEGORY_PRIVATE) {
            $objOtherUser = User::getById($objContact->otherId);
            if (empty($objOtherUser)) {
                Log::error('no such user ' . $objContact->otherId);
            } else {
                $arrUserIds = [$objUser->id,$objOtherUser->id];
                $arrOtherUsers = [$objUser,$objOtherUser];
            }
        } else {
            $arrUserIds = [];
            foreach ($arrOtherChats as $objOtherChat) {
                $arrUserIds[] = (int) $objOtherChat->selfId;
            }
            $arrOtherUsers = User::getByIds($arrUserIds);
        }
        // arrHash 存储 用户自己的用户 ID 到自己接收当前聊天消息 id 的映射
        $arrHash = [];
        foreach ($arrOtherChats as $objChat){
            $arrHash[$objChat->selfId] = $objChat->id; 
        }
        if($objMessage->messageType == Message::TYPE_IMG) {
            $objMessage->info = Image::toHeaderUrl($objMessage->info);
        }
        // Log::info('other users: '.serialize($arrOtherUsers));
        Log::info('arrHash '.print_r($arrHash,true));
        foreach ($arrOtherUsers as $objOtherUser) {
            $socketId = $objOtherUser->socketId;
            Log::info('userId '.$objOtherUser->id.' socketId '.$socketId);
            if (!empty($socketId)) {
                $arrContent = ['chatId' => $arrHash[$objOtherUser->id], 'senderId' => $objMessage->senderId, 'senderHeader' => Image::toHeaderUrl($objMessage->senderHeader),
                    'messageType' => $objMessage->messageType, 'info' => $objMessage->info, 'sendTime' => $objMessage->sendTime, 'id' => $objMessage->id,
                    'senderNickname' => $objMessage->senderNickname];
                $client = new Client(['verify' => false]);
                $url = 'http://'.config('socket.host').':'.config('socket.port').'/send_message?socket=' . $socketId . '&content=' . json_encode($arrContent);
                $res = $client->get($url);
                $statusCode = $res->getStatusCode();
                if ($statusCode == 200) {
                    $retContent = $res->getBody()->getContents();
                    Log::info('retContent ' . $retContent);
                } else {
                    Log::error('send msg to socket ' . $socketId . ' fail');
                }
            }
        }
        $objMessage->senderHeader = Image::toHeaderUrl($objMessage->senderHeader);
        // 进行钩子，当发送消息给客服而客服不在线或者已经下班时，发送客服已经离线的消息
        if($category == Contact::CATEGORY_PRIVATE&&!empty($objOtherUser)&&$objOtherUser->userType==User::TYPE_CUSTOMER_SERVICE){
            $hour = (int)date('H');
            if($hour>=22||$hour<=10){
                // 当客服已经下班时，发送一条客服已经下班的消息
                self::sendMessage($objOtherChat, $objOtherUser, $messageType, '人工客服下班啦， 您如有任何疑问和建议，烦请留言反馈，我们将会在第一时间给您回复。');
            }
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $objMessage
        ];
    }

}
