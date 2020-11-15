<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Library\Image;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
// use App\Models\ServiceAccount;
// use App\Models\MessageUnread;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Service\UserService;
use App\Library\ApiUtil;

class UserController extends \App\Http\Controllers\Controller
{

    // 用户的基本信息
    public function info(Request $request)
    {
        // $token = $request->input('token');
        // $objUser = User::getByToken($token);
        $objUser = auth()->user();
        $arrInfo = [
            'refCode' => $objUser->refCode,
            'header' => Image::toHeaderUrl($objUser->header),
            'username' => $objUser->username,
            'userId' => $objUser->id,
            'nickname' => $objUser->nickname,
            'userType' => $objUser->userType,
            'telephone' => $objUser->telephone
        ];
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrInfo
        ];
    }

    // 用户余额信息
    public function walletInfo(Request $request)
    {
        $objUser = auth()->user();
        ApiUtil::transferToMainBalance($objUser);
        $objUser = User::getById($objUser->id);
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => [
                'balance' => $objUser->balance,
                'freezeBalance' => $objUser->freezeBalance
            ]
        ];
    }

    // 用户联系人列表
    public function contacts(Request $request)
    {
        $objUser = auth()->user();
        $arrData = Contact::getUserContacts($objUser->id);
        foreach ($arrData as &$objContact) {
            $objContact->header = Image::toHeaderUrl($objContact->header);
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrData
        ];
    }

    // 给用户绑定socketId
    public function bindSocket(Request $request)
    {
        $socketId = $request->input('socket_id');
        $objUser = auth()->user();
        $objUser->socketId = $socketId;
        $objUser->save();
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS
        ];
    }

    // 更改用户昵称
    public function changeNickname(Request $request)
    {
        $nickname = $request->input('nickname');
        $nicknameLength = mb_strlen($nickname, 'utf8');
        if ($nicknameLength < 2 || $nicknameLength > 8) {
            return [
                'code' => ErrorCode::ERROR_INVALID_NICKNAME,
                'msg' => ErrorMsg::ERROR_INVALID_NICKNAME
            ];
        }
        $objUser = auth()->user();
        $objUser->nickname = $nickname;
        if (! $objUser->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    // 更改用户头像
    public function changeHeader(Request $request)
    {
        $headerName = $_FILES['header']['name'];
        $headerTmpName = $_FILES['header']['tmp_name'];
        Log::info('headerName=' . $headerName);
        Log::info('headerTmpName=' . $headerTmpName);
        Log::info('error=' . $_FILES['header']['error']);
        if (empty($headerName)) {
            return [
                'code' => ErrorCode::ERROR_HEADER_REQUIRED,
                'msg' => ErrorMsg::ERROR_HEADER_REQUIRED
            ];
        }
        if (empty($headerTmpName) || ! is_file($headerTmpName)) {
            return [
                'code' => ErrorCode::ERROR_HEADER_INVALID,
                'msg' => ErrorMsg::ERROR_HEADER_INVALID
            ];
        }
        $objUser = auth()->user();
        // 处理头像
        $headerUUID = md5(microtime(true) . mt_Rand() . $objUser->username . $objUser->telephone);
        try {
            $headerFile = '/tmp/' . $headerUUID . '.png';
            imagepng(imagecreatefromstring(file_get_contents($headerTmpName)), $headerFile);
            $dest = storage_path('app/public/' . $headerUUID . '.png');
            Image::resizeImage($headerFile, $dest, 96, 96, false); // 微信头像是 96px*96px的
            unlink($headerFile);
        } catch (\Throwable $t) {
            Log::info($t->getMessage() . ' ' . $t->getCode());
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $objUser->header = $headerUUID;
        if (! $objUser->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS,
                'data' => [
                    'header' => Image::toHeaderUrl($headerUUID)
                ]
            ];
        }
    }

    // 游客用户绑定
    public function tryPlayerBind(Request $request)
    {
        $telephone = $request->input('telephone');
        $verifyCode = $request->input('verifyCode');
        $password = $request->input('password');
        if (! preg_match('/^1[34578]\d{9}$/', $telephone)) {
            return [
                'code' => ErrorCode::ERROR_INVALID_TELEPHONE,
                'msg' => ErrorMsg::ERROR_INVALID_TELEPHONE
            ];
        }
        $codeVerify = Redis::get('verify_code_' . $telephone);
        if (empty($codeVerify)) {
            return [
                'code' => ErrorCode::ERROR_EXPIRE_CODE,
                'msg' => ErrorMsg::ERROR_EXPIRE_CODE
            ];
        }
        if ($verifyCode != $codeVerify) {
            return [
                'code' => ErrorCode::ERROR_VERIFY_CODE,
                'msg' => ErrorMsg::ERROR_VERIFY_CODE
            ];
        }
        if (preg_match('/^[0-9]+$/', $password) || preg_match('/^[A-Za-z]+$/', $password) || !preg_match('/^[A-Za-z0-9]+$/', $password) || strlen($password) < 8 || strlen($password) > 12) {
            return [
                'code' => ErrorCode::ERROR_INVALID_PASSWORD,
                'msg' => ErrorMsg::ERROR_INVALID_PASSWORD
            ];
        }
        $objExistUser = User::getByTelephone($telephone);
        if (! empty($objExistUser)) {
            return [
                'code' => ErrorCode::ERROR_DUPLICATE_TELEPHONE,
                'msg' => ErrorMsg::ERROR_DUPLICATE_TELEPHONE
            ];
        }
        $objUser = auth()->user();
        $objUser->telephone = $telephone;
        $objUser->password = $password;
        // 之前可能给试玩用户加了钱的，全部要清掉，TODO: 交易记录也应该要清掉
        $objUser->balance = 0;
        $objUser->freezeBalance = 0;
        $objUser->userType = User::TYPE_REGISTER_PLAYER;
        if (! $objUser->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            // 游客用户绑定后要重新加群
            UserService::afterRegister($objUser);
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    // 修改密码
    public function changePassword(Request $request)
    {
        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');
        if (preg_match('/^[0-9]+$/', $newPassword) || preg_match('/^[A-Za-z]+$/', $newPassword) || !preg_match('/^[A-Za-z0-9]+$/', $newPassword) || strlen($newPassword) < 8 || strlen($newPassword) > 12) {
            return [
                'code' => ErrorCode::ERROR_INVALID_PASSWORD,
                'msg' => ErrorMsg::ERROR_INVALID_PASSWORD
            ];
        }
        $objUser = auth()->user();
        if ($oldPassword != $objUser->password) {
            return [
                'code' => ErrorCode::ERROR_WRONG_PASSWORD,
                'msg' => ErrorMsg::ERROR_WRONG_PASSWORD
            ];
        }
        $objUser->password = $newPassword;
        if (! $objUser->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    // 与我私聊对话的另外一个用户的个人信息
    public function otherUserInfo(Request $request)
    {
        $objUser = auth()->user();
        $chatId = $request->input('chatId');
        if (empty($chatId)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $objChat = Contact::getById($chatId);
        if (empty($objChat)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        if ($objChat->selfId != $objUser->id) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $otherId = $objChat->otherId;
        $objOtherUser = User::getById($otherId);
        if (empty($objOtherUser)) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        $arrData = [
            'userType' => $objOtherUser->userType,
            'username' => $objOtherUser->username,
            'nickname' => $objOtherUser->nickname,
            'header' => Image::toHeaderUrl($objOtherUser->header)
        ];
        // 客服或者自己的上级代理可以看到自己的余额
        if ($objUser->userType == User::TYPE_CUSTOMER_SERVICE || $objOtherUser->parentId == $objUser->id) {
            $arrData['balance'] = $objOtherUser->balance;
            $arrData['freezeBalance'] = $objOtherUser->freezeBalance;
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrData
        ];
    }
}