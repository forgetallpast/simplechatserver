<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Controllers\User;
use Illuminate\Http\Request;
use App\Models\User;
use App\Library\Image;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use App\Models\MessageHistory;
use App\Models\Message;
use App\Service\MessageService;
use App\Models\Contact;
use App\Library\ApiUtil;
use Illuminate\Support\Facades\Log;

class MessageController extends \App\Http\Controllers\Controller{
    // 对话框中取历史记录
    public function chatHistory(Request $request){
        $objUser = auth()->user();
        $selfId = (int)$objUser->id;
        $chatId = (int)$request->input('chatId');
        $maxMessageId = (int)($request->input('minMessageId', 0));
        $limit = (int)$request->input('limit', 100);
        // 点进了对话框保证钱包在主钱包上
        ApiUtil::transferToMainBalance($objUser);
        $arrHistoryMessages = MessageHistory::getHistoryMessages(['selfId'=>$selfId,'chatId'=>$chatId,'maxMessageId'=>$maxMessageId,'limit'=>$limit]);
        $arrMessageIds = [];
        foreach ($arrHistoryMessages as $objHistoryMessage){
            $arrMessageIds[] = (int)$objHistoryMessage->messageId;
        }
        $arrMessages = Message::getMessages($arrMessageIds);
        foreach ($arrMessages as &$objMessage){
            $objMessage->senderHeader = Image::toHeaderUrl($objMessage->senderHeader);
            if($objMessage->messageType == Message::TYPE_IMG){
                $objMessage->info = Image::toHeaderUrl($objMessage->info);
            }
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrMessages
        ];
    }
    // 发送消息
    public function sendMessage(Request $request){
        $objUser = auth()->user();
        $selfId = (int)$objUser->id;
        $chatId = (int)$request->input('chatId');
        $messageType = (int)$request->input('messageType', Message::TYPE_TEXT);
        $info = (string)$request->input('info');
        $objContact = Contact::getById($chatId);
        // 验证会话id
        if(empty($objContact)||$objContact->selfId != $selfId){
            return [
                'code' => ErrorCode::ERROR_CHAT_NOT_EXIST,
                'msg' => ErrorMsg::ERROR_CHAT_NOT_EXIST
            ];
        }
        return MessageService::sendMessage($objContact,$objUser,$messageType,$info);
    }
    // 发送图片消息
    public function sendImage(Request $request){
        $objUser = auth()->user();
        $selfId = (int)$objUser->id;
        $chatId = (int)$request->input('chatId');
        $messageType = (int)$request->input('messageType', Message::TYPE_IMG);
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageId = md5(microtime(true) . mt_Rand().$chatId);
        try {
            imagepng(imagecreatefromstring(file_get_contents($imageTmpName)), storage_path('app/public/'.$imageId.'.png'));
        } catch (Throwable $t) {
            Log::info($t->getMessage . ' ' . $t->getCode());
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $info = $imageId;
        $objContact = Contact::getById($chatId);
        // 验证会话id
        if(empty($objContact)||$objContact->selfId != $selfId){
            return [
                'code' => ErrorCode::ERROR_CHAT_NOT_EXIST,
                'msg' => ErrorMsg::ERROR_CHAT_NOT_EXIST
            ];
        }
        return MessageService::sendMessage($objContact,$objUser,$messageType,$info);
    }
    // 最后的最新消息，微信首页用
    public function lastMessage(Request $request){
        $objUser = auth()->user();
        $selfId = (int)$objUser->id;
        Log::info('transfer aws to main balance');
        $arrContacts = Contact::getOrderedUserContacts($selfId);
        foreach ($arrContacts as &$objContact){
            $objContact->header = Image::toHeaderUrl($objContact->header);
            $objContact->chatId = $objContact->id;
        }
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS,'data'=>$arrContacts];
    }
    // 清除某个chatId 的未读消息数目
    public function clearUnreadMsgCount(Request $request){
        $objUser = auth()->user();
        $selfId = (int)$objUser->id;
        $chatId = (int)$request->input('chatId');
        if(empty($chatId)){
            return ['code'=> ErrorCode::ERROR_CHAT_NOT_EXIST, 'msg' => ErrorMsg::ERROR_CHAT_NOT_EXIST];
        }
        $objChat = Contact::getById($chatId);
        if(empty($objChat)){
            return ['code'=> ErrorCode::ERROR_CHAT_NOT_EXIST, 'msg' => ErrorMsg::ERROR_CHAT_NOT_EXIST];
        }
        $objChat->unreadCount = 0;
        $objChat->save();
        return ['code' => ErrorCode::SUCCESS, 'msg' => ErrorMsg::SUCCESS];
    }
}
