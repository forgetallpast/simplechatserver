<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use App\Library\Image;
use Illuminate\Support\Facades\Log;
use App\Service\GroupService;
use App\Models\Contact;
use App\Models\Group;
use App\Service\MessageService;
use App\Models\Message;
use App\Models\GroupMember;
use App\Models\User;
use App\Service\UserService;

class GroupController extends \App\Http\Controllers\Controller {

    // 建群
    public function createGroup(Request $request) {
        $headerName = $_FILES['header']['name'];
        $headerTmpName = $_FILES['header']['tmp_name'];
        if (empty($headerName)) {
            return [
                'code' => ErrorCode::ERROR_HEADER_REQUIRED,
                'msg' => ErrorMsg::ERROR_HEADER_REQUIRED
            ];
        }
        if (empty($headerTmpName) || !is_file($headerTmpName)) {
            return [
                'code' => ErrorCode::ERROR_HEADER_INVALID,
                'msg' => ErrorMsg::ERROR_HEADER_INVALID
            ];
        }
        $objUser = auth()->user();
        // 处理头像
        $headerUUID = md5(microtime(true) . mt_Rand() . $objUser->username . $objUser->id);
        try {
            $headerFile = '/tmp/' . $headerUUID . '.png';
            imagepng(imagecreatefromstring(file_get_contents($headerTmpName)), $headerFile);
            $dest = storage_path('app/public/' . $headerUUID . '.png');
            Image::resizeImage($headerFile, $dest, 96, 96, false); // 微信头像是 96px*96px的
            unlink($headerFile);
        } catch (\Throwable $t) {
            Log::info($t->getMessage . ' ' . $t->getCode());
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $groupName = $request->input('groupName');
        $groupType = $request->input('groupType');
        return GroupService::createGroup($groupName, '', $headerUUID, $groupType, $objUser);
    }

    // 代理群信息
    public function groupInfoByChat(Request $request) {
        $chatId = (int) $request->input('chat_id');
        $objUser = auth()->user();
        $objChat = Contact::getById($chatId);
        if (empty($objChat) || $objChat->selfId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_CHAT_NOT_EXIST,
                'msg' => ErrorMsg::ERROR_CHAT_NOT_EXIST
            ];
        }
        $groupId = (int) $objChat->otherId;
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS,
                'data' => [
                    'groupId' => $groupId,
                    'ownerId' => $objGroup->ownerId,
                    'bulletin' => $objGroup->bulletin,
                    'groupName' => $objGroup->groupName,
                    'memberCount' => $objGroup->memberCount,
                    'groupType' => $objGroup->groupType,
                    'disableTalk' => $objGroup->disableTalk
                ]
            ];
        }
    }

    // 更改公告
    public function changeBulletin(Request $request) {
        $groupId = (int) $request->input('groupId');
        $bulletin = $request->input('bulletin');
        $objUser = auth()->user();
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        if ($objGroup->ownerId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $chatId = (int) $request->input('chatId');
        $objContact = Contact::getById($chatId);
        if (empty($objContact)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $objGroup->bulletin = $bulletin;
        if (!$objGroup->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            MessageService::sendMessage($objContact, $objUser, Message::TYPE_TEXT, $bulletin);
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    // 列出是群主好友但还不在群里的所有用户
    public function usersCanAdd(Request $request) {
        $groupId = (int) $request->input('groupId');
        $objUser = auth()->user();
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        if ($objGroup->ownerId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $arrFriends = Contact::getPrivateContacts($objUser->id);
        $arrMembers = GroupMember::getMembers($groupId);
        $arrMemberIds = [];
        foreach ($arrMembers as $objMember) {
            $arrMemberIds[] = (int) $objMember->userId;
        }
        $arrCanAddIds = [];
        foreach ($arrFriends as $objFriend) {
            if (!in_array((int) $objFriend->otherId, $arrMemberIds)) {
                $arrCanAddIds[] = (int) $objFriend->otherId;
            }
        }
        $arrCanAddUsers = User::getByIds($arrCanAddIds);
        $arrRetUsers = [];
        foreach ($arrCanAddUsers as $objUser) {
            $arrRetUsers[] = [
                'id' => $objUser->id,
                'nickname' => $objUser->nickname,
                'header' => Image::toHeaderUrl($objUser->header)
            ];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrRetUsers
        ];
    }

    // 增加群成员
    public function addGroupMembers(Request $request) {
        $groupId = $request->input('groupId');
        $userIds = $request->input('userIds');
        $objUser = auth()->user();
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        if ($objGroup->ownerId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $arrUsers = User::getByIds($userIds);
        foreach ($arrUsers as $addUser) {
            UserService::joinGroup($addUser, $objGroup);
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS
        ];
    }

    // 列出群成员
    public function groupMembers(Request $request) {
        $groupId = $request->input('groupId');
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $arrMembers = GroupMember::getMembers($groupId);
        $arrMemberIds = [];
        foreach ($arrMembers as $objMember) {
            $arrMemberIds[] = (int) $objMember->userId;
        }
        $arrMembers = User::getByIds($arrMemberIds);
        $arrRetUsers = [];
        foreach ($arrMembers as $objUser) {
            $arrRetUsers[] = [
                'id' => $objUser->id,
                'nickname' => $objUser->nickname,
                'header' => Image::toHeaderUrl($objUser->header)
            ];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrRetUsers
        ];
    }

    // 更改禁言状态
    public function toggleDisableTalk(Request $request) {
        $groupId = (int) $request->input('groupId');
        $disableTalk = (int) (bool) $request->input('disableTalk');
        $objUser = auth()->user();
        $objGroup = Group::find($groupId);
        if (empty($objGroup)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        if ($objGroup->ownerId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $objGroup->disableTalk = $disableTalk;
        if (!$objGroup->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            $chatId = (int) $request->input('chatId');
            $objContact = Contact::getById($chatId);
            if (empty($objContact)) {
                return [
                    'code' => ErrorCode::ERROR_SERVER_ERROR,
                    'msg' => ErrorMsg::ERROR_SERVER_ERROR
                ];
            }
            $message = $disableTalk ? '本群已被禁言，您可以刷新页面安心游戏' : '本群已经取消禁言，您可以刷新页面开始发言';
            MessageService::sendMessage($objContact, $objUser, Message::TYPE_TEXT, $message);
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

}
