<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers\Guest;

use Illuminate\Http\Request;
use App\Library\Message;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Library\LoginMethod;
use Illuminate\Support\Facades\Log;
use App\Library\Image;
use Illuminate\Support\Facades\Storage;
use Throwable;
//use App\Service\MessageService;
use App\Service\UserService;
use App\Models\Staff;

class GuestController extends \App\Http\Controllers\Controller {

    public function login(Request $request) {
        $loginMethod = $request->input('loginMethod');
        $username = trim($request->input('username'));
        $telephone = trim($request->input('telephone'));
        $password = trim($request->input('password'));
        // 可能会有多种登录方式，包括用户名密码登录，手机号码密码登录，第三方api登录
        if ($loginMethod == LoginMethod::MOBILE_LOGIN) {
            if (empty($telephone)) {
                return [
                    'code' => ErrorCode::ERROR_TELEPHONE_REQUIRED,
                    'msg' => ErrorMsg::ERROR_TELEPHONE_REQUIRED
                ];
            }
            if (empty($password)) {
                return [
                    'code' => ErrorCode::ERROR_PASSWORD_REQUIRED,
                    'msg' => ErrorMsg::ERROR_PASSWORD_REQUIRED
                ];
            }
            $objUser = User::loginByTelephone($telephone, $password);
            if (!empty($objUser)) {
                $token = md5(microtime(true) . mt_Rand() . $telephone);
                User::updateUserToken($objUser, $token);
                return [
                    'code' => ErrorCode::SUCCESS,
                    'msg' => ErrorMsg::SUCCESS,
                    'token' => $token
                ];
            } else {
                return [
                    'code' => ErrorCode::ERROR_TELEPHONE_OR_PASSWORD,
                    'msg' => ErrorMsg::ERROR_TELEPHONE_OR_PASSWORD
                ];
            }
        } else if ($loginMethod == LoginMethod::USERNAME_LOGIN) {
            if (empty($username)) {
                return [
                    'code' => ErrorCode::ERROR_USERNAME_REQUIRED,
                    'msg' => ErrorMsg::ERROR_USERNAME_REQUIRED
                ];
            }
            if (empty($password)) {
                return [
                    'code' => ErrorCode::ERROR_PASSWORD_REQUIRED,
                    'msg' => ErrorMsg::ERROR_PASSWORD_REQUIRED
                ];
            }
            $objUser = User::loginByUsername($username, $password);
            if (!empty($objUser)) {
                $token = md5(microtime(true) . mt_Rand() . $username);
                User::updateUserToken($objUser, $token);
                // MessageService::sendSystemTextMessage($objUser->id, '欢迎登录！');
                return [
                    'code' => ErrorCode::SUCCESS,
                    'msg' => ErrorMsg::SUCCESS,
                    'token' => $token
                ];
            } else {
                return [
                    'code' => ErrorCode::ERROR_USERNAME_OR_PASSWORD,
                    'msg' => ErrorMsg::ERROR_USERNAME_OR_PASSWORD
                ];
            }
        } else {
            return [
                'code' => ErrorCode::ERROR_UNSUPPORTED_LOGIN,
                'msg' => ErrorMsg::ERROR_UNSUPPORTED_LOGIN
            ];
        }
    }
    
    /*
     * 注册逻辑，校验信息，插入用户，互相加所有客服人员为联系，互相加自己代理为联系人
     */
    public function register(Request $request) {
        $nickname = $request->input('nickname');
        $telephone = $request->input('telephone');
        $verifyCode = $request->input('verifyCode');
        $username = $request->input('username');
        $password = $request->input('password');
        $nicknameLength = mb_strlen($nickname, 'utf8');
        /*
        $headerName = $_FILES['header']['name'];
        $headerTmpName = $_FILES['header']['tmp_name'];
        Log::info('headerName=' . $headerName);
        Log::info('headerTmpName=' . $headerTmpName);
        Log::info('error='.$_FILES['header']['error']);
        if (empty($headerName)) {
            return [
                'code' => ErrorCode::ERROR_HEADER_REQUIRED,
                'msg' => ErrorMsg::ERROR_HEADER_REQUIRED
            ];
        }
        if(empty($headerTmpName)||!is_file($headerTmpName)){
            return [
                'code' => ErrorCode::ERROR_HEADER_INVALID,
                'msg' => ErrorMsg::ERROR_HEADER_INVALID
            ];
        }
        */
        if ($nicknameLength < 2 || $nicknameLength > 8) {
            return [
                'code' => ErrorCode::ERROR_INVALID_NICKNAME,
                'msg' => ErrorMsg::ERROR_INVALID_NICKNAME
            ];
        }
        if (!preg_match('/^1[34578]\d{9}$/', $telephone)) {
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
        if (preg_match('/^[0-9]+$/', $username) || preg_match('/^[A-Za-z]+$/', $username) || !preg_match('/^[A-Za-z0-9]+$/', $username) || strlen($username) < 6 || strlen($username) > 12) {
            return [
                'code' => ErrorCode::ERROR_INVALID_USERNAME,
                'msg' => ErrorMsg::ERROR_INVALID_USERNAME
            ];
        }
        if (preg_match('/^[0-9]+$/', $password) || preg_match('/^[A-Za-z]+$/', $password) || !preg_match('/^[A-Za-z0-9]+$/', $password) || strlen($password) < 8 || strlen($password) > 12) {
            return [
                'code' => ErrorCode::ERROR_INVALID_PASSWORD,
                'msg' => ErrorMsg::ERROR_INVALID_PASSWORD
            ];
        }
        $objExistsUsername = User::getByUsername($username);
        if ($objExistsUsername) {
            return [
                'code' => ErrorCode::ERROR_DUPLICATE_USERNAME,
                'msg' => ErrorMsg::ERROR_DUPLICATE_USERNAME
            ];
        }
        $objExistsTelephone = User::getByTelephone($telephone);
        if ($objExistsTelephone) {
            return [
                'code' => ErrorCode::ERROR_DUPLICATE_TELEPHONE,
                'msg' => ErrorMsg::ERROR_DUPLICATE_TELEPHONE
            ];
        }
        // 处理头像
        /*
        $headerUUID = md5(microtime(true) . mt_Rand() . $username . $telephone);
        try {
            $headerFile = '/tmp/' . $headerUUID.'.png';
            imagepng(imagecreatefromstring(file_get_contents($headerTmpName)), $headerFile);
            $dest = storage_path('app/public/'.$headerUUID.'.png');
            Image::resizeImage($headerFile, $dest, 96, 96, false); // 微信头像是 96px*96px的
            unlink($headerFile);
        } catch (Throwable $t) {
            Log::info($t->getMessage . ' ' . $t->getCode());
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }
        */
        $agentRefCode = (string)$request->input('agentRefCode');
        if(!empty($agentRefCode)){
            $objAgent = User::getByRefCode($agentRefCode);
        }
        $objUser = new User();
        $objUser->nickname = $nickname;
        $objUser->username = $username;
        $objUser->password = $password;
        $objUser->telephone = $telephone;
        $objUser->header = 'guest_header_'.rand(1,108);
        // $objUser->header = $headerUUID;
        $objUser->userType = User::TYPE_REGISTER_PLAYER;
        if(isset($objAgent)&&!empty($objAgent)){
            $objUser->parentId = $objAgent->id;
        }
        if (!$objUser->save()) {
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        } else {
            // 注册成功，插入一些注册后需要做的事情，比如给用户加所有的客服好友，加系统群组
            UserService::afterRegister($objUser);
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    public function sendSmsCode(Request $request) {
        $telephone = $request->input('telephone');
        $r = $request->input('r');
        $k = $request->input('k');
        // k = md5(md5(r+this.telephone+r)+r)
        if (!preg_match("/^1[34578]\d{9}$/", $telephone)) {
            return [
                'code' => ErrorCode::ERROR_INVALID_TELEPHONE,
                'msg' => ErrorMsg::ERROR_INVALID_TELEPHONE
            ];
        }
        if ($k != md5(md5($r . $telephone . $r) . $r)) {
            return [
                'code' => ErrorCode::ERROR_SIGN,
                'msg' => ErrorMsg::ERROR_SIGN
            ];
        }
        $verifyCode = rand(1000, 9999);
        Redis::set('verify_code_' . $telephone, $verifyCode, 'EX', 180);
        $sendSmsResult = Message::sendSmsVerifyCode($telephone, $verifyCode);
        Log::info('send sms code ' . $verifyCode);
        // $sendSmsResult = true;
        if (!$sendSmsResult) {
            return [
                'code' => ErrorCode::ERROR_SMS_FAIL,
                'msg' => ErrorMsg::ERROR_SMS_FAIL
            ];
        } else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }

    // 更改密码的短信验证码
    public function sendModifyPasswordSmsCode(Request $request){
        $telephone = $request->input('telephone');
        $objUser = User::getByTelephone($telephone);
        if(empty($objUser)){
            return [
                'code' => ErrorCode::ERROR_NO_SUCH_USER,
                'msg' => ErrorMsg::ERROR_NO_SUCH_USER
            ];
        }
        return $this->sendSmsCode($request);
    }

    // 更改密码
    public function modifyPassword(Request $request){
        $telephone = $request->input('telephone');
        $verifyCode = $request->input('verifyCode');
        $password = $request->input('password');
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
        $objUser = User::getByTelephone($telephone);
        if(empty($objUser)){
            return [
                'code' => ErrorCode::ERROR_NO_SUCH_USER,
                'msg' => ErrorMsg::ERROR_NO_SUCH_USER
            ];
        }
        if (preg_match('/^[0-9]+$/', $password) || strlen($password) < 6 || strlen($password) > 20) {
            return [
                'code' => ErrorCode::ERROR_INVALID_PASSWORD,
                'msg' => ErrorMsg::ERROR_INVALID_PASSWORD
            ];
        }
        $objUser->password = $password;
        if (!$objUser->save()) {
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
    
    // 管理员登录
    public function adminLogin(Request $request){
        $username = $request->input('username');
        $password = $request->input('password');
        if(empty($username)||empty($password)){
            return ['code' => ErrorCode::ERROR_LACK_PARAMS, 'msg' => ErrorMsg::ERROR_LACK_PARAMS];
        }
        $objStaff = Staff::login($username, $password);
        if(empty($objStaff)){
            return ['code' => ErrorCode::ERROR_USERNAME_OR_PASSWORD, 'msg' => ErrorMsg::ERROR_USERNAME_OR_PASSWORD];
        }
        else{
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS,
                'data' => [
                    'token' => $objStaff->token,
                    'expire' => 3600*24*30,
                    'username' => $username
                ]
            ];
        }
    }
    
    // 游客 用户
    public function tryPlay(Request $request){
        $agentRefCode = $request->input('agentRefCode', '');
        $objUser = UserService::createGuestPlayer($agentRefCode);
        if(empty($objUser)){
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }else{
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS,
                'data' => [
                    'token' => $objUser->token
                ]
            ];
        }
    }

}
