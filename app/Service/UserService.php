<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Service;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Log;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use Illuminate\Support\Str;
use App\Models\AgentUserLevel;
use App\Models\Withdraw;
use App\Library\Telegram;
use App\Models\Transaction;
class UserService {
    // 创建游客
    public static function createGuestPlayer($agentRefCode=''){
        $objUser = new User();
        $username = '';
        do{
            $username = 'g_'.strtolower(Str::random(8));
            $objExist = User::getByUsername($username);
        }while(!empty($objExist));
        $objUser->username = $username;
        $objUser->password = Str::random(16);
        $objUser->nickname = '游客'.strtolower(Str::random(6));
        $objUser->header = 'guest_header_'.rand(1,108);
        $objUser->userType = User::TYPE_GUEST_PLAYER;
        $token = md5(microtime(true) . mt_Rand() . $username);
        $objUser->token = $token;
        $objUser->balance = 10000; // 游客有1万金币
        if(!empty($agentRefCode)){
            $objAgent = User::getByRefCode($agentRefCode);
        }
        if(isset($objAgent)&&!empty($objAgent)){
            $objUser->parentId = $objAgent->id;
        }
        if($objUser->save()){
            self::afterRegister($objUser);
            return $objUser;
        }else{
            return false;
        }
    }
    
    // 用户注册完后的钩子
    // 先加入自己上级创建的所有群，然后把所有客服和用户互相加好友
    public static function afterRegister($objUser){
        if($objUser->userType==User::TYPE_REGISTER_PLAYER){
            // 加入上级创建的所有群
            $parentId = (int)$objUser->parentId;
            $arrGroups = Group::getMyOwnerGroups($parentId);
            foreach ($arrGroups as $objGroup){
                // TODO: 区分
                // if($objGroup->groupType!=Group::TYPE_TRY_PLAYER){
                    self::joinGroup($objUser, $objGroup);
                // }
            }
        }else if($objUser->userType==User::TYPE_GUEST_PLAYER){
            // TODO: 加入游客群
            $parentId = (int)$objUser->parentId;
        }
        // 和所有客服互相加联系人
        $arrCustomerServices = User::getAllCustomerService();
        foreach ($arrCustomerServices as $objCustomerService){
            self::addFriend($objUser, $objCustomerService);
        }
        // 如果是有上级代理的，一路记录上级代理
        $parentId = (int)$objUser->parentId;        
        $level = 1;
        while(!empty($parentId)){
            $objAgentUserLevel = new AgentUserLevel();
            $objAgentUserLevel->agentId = $parentId;
            $objAgentUserLevel->userId = $objUser->id;
            $objAgentUserLevel->level = $level;
            if(!$objAgentUserLevel->save()){
                Log::error('log '.$parentId.' '.$objUser->id.' level '.$level.' fail');
            }
            $parentUser = User::getById($parentId);
            if(!empty($parentUser)){
                // 加直接上级为好友
                if($level == 1){
                    self::addFriend($parentUser, $objUser);
                }
                $parentId = (int)$parentUser->parentId;
            }else{
                $parentId = 0;
            }
            ++$level;
        }
    }
    // 用户加入群组
    public static function joinGroup($objUser,$objGroup){
        if(GroupMember::isJoinGroup($objUser->id, $objGroup->id)){
            Log::info('user '.$objUser->id.' already join '.$objGroup->id);
            return true;
        }
        // 影响 group group_member contact 三个表
        // 多了一个群成员
        $objGroup->increment('memberCount', 1);
        $objGroupMember = new GroupMember();
        $objGroupMember->userId = $objUser->id;
        $objGroupMember->groupId = $objGroup->id;
        if(!$objGroupMember->save()){
            Log::error('something wrong to save objGroupMember '.$objUser->id.' '.$objGroup->id);
            return false;
        }
        $objContact = new Contact();
        $objContact->selfId = $objUser->id;
        $objContact->otherId = $objGroup->id;
        $objContact->category = Contact::CATEGORY_GROUP;
        $objContact->header = $objGroup->header;
        $objContact->nickname = $objGroup->groupName;
        if(!$objContact->save()){
            Log::error('something wrong to save group contact '.$objUser->id.' '.$objGroup->id);
            return false;
        }
        return true;
    }
    // 用户互相加好友
    public static function addFriend($objUser1, $objUser2){
        if($objUser1->id == $objUser2->id){
            Log::info($objUser1->id.' '.$objUser2->id.' is the same');
            return true;
        }
        if(Contact::isFriend($objUser1->id, $objUser2->id)){
            Log::info($objUser1->id.' '.$objUser2->id.' already friends');
            return true;
        }
        $objContact1 = new Contact();
        $objContact1->selfId = $objUser1->id;
        $objContact1->otherId= $objUser2->id;
        $objContact1->category = Contact::CATEGORY_PRIVATE;
        $objContact1->header = $objUser2->header;
        $objContact1->nickname = $objUser2->nickname;
        if(!$objContact1->save()){
            Log::error('something wrong save add friend contact '.$objUser1->id.' '.$objUser2->id);
            return false;
        }
        $objContact2 = new Contact();
        $objContact2->selfId = $objUser2->id;
        $objContact2->otherId= $objUser1->id;
        $objContact2->category = Contact::CATEGORY_PRIVATE;
        $objContact2->header = $objUser1->header;
        $objContact2->nickname = $objUser1->nickname;
        if(!$objContact2->save()){
            Log::error('something wrong save add friend contact '.$objUser2->id.' '.$objUser1->id);
            return false;
        }
        return true;
    }
    // 更改用户的余额
    public static function changeBalance(&$objUser, $amountCent){
        DB::beginTransaction();
        $objUser = User::getById($objUser->id);
        if($amountCent>0){
            $objUser->increment('balance', $amountCent);
        }else{
            // 注意可能出现钱不够的情况！
            $objUser->increment('balance', $amountCent);
            if($objUser->balance < 0){
                DB::rollBack();
                return ['code'=> ErrorCode::ERROR_BALANCE_INSUFFICIENT,'msg'=> ErrorMsg::ERROR_BALANCE_INSUFFICIENT];
            }
        }
        DB::commit();
        $eventDesc = '用户余额变更 '.$objUser->id.' '.$objUser->username.' 变更额度 '.($amountCent/100).' 变更后 '.($objUser->balance/100);
        Log::info($eventDesc);
        Telegram::sendMessage($eventDesc);
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS];
    }
    // 用户提款请求
    public static function addWithdrawal(&$objUser, $objBankcard, $amountCent){
        if($amountCent<=0){
            return ['code' => ErrorCode::ERROR_WITHDRAW_POSITIVE, 'msg' => ErrorMsg::ERROR_WITHDRAW_POSITIVE];
            // return ['code'=>ErrorCode::ERROR_INVALID_PARAM,'msg'=>ErrorMsg::ERROR_INVALID_PARAM];
        }
        DB::beginTransaction();
        $affectedRows = User::where('id', $objUser->id)->where('balance', '>=', $amountCent)->update([
            'balance' => DB::raw('balance-' . $amountCent),
            'freezeBalance' => DB::raw('freezeBalance+'.$amountCent)]);
        if($affectedRows!==1){
            DB::rollBack();
            return ['code'=> ErrorCode::ERROR_BALANCE_INSUFFICIENT,'msg'=> ErrorMsg::ERROR_BALANCE_INSUFFICIENT];
        }
        $objWithdraw = new Withdraw();
        $objWithdraw->userId = $objUser->id;
        $objWithdraw->userBankcardId = $objBankcard->id;
        $objWithdraw->amount = $amountCent;
        $objWithdraw->status = Withdraw::APPLY;
        if(!$objWithdraw->save()){
            DB::rollBack();
            return ['code' => ErrorCode::ERROR_SERVER_ERROR,'msg' => ErrorMsg::ERROR_SERVER_ERROR];
        }
        $objUser = User::getById($objUser->id);
        // 记录用户提现冻结数据
        $objTransaction = new Transaction();
        $objTransaction->userId = $objUser->id;
        $objTransaction->type = Transaction::WITHDRAW;
        $objTransaction->changeAmount = $amountCent;
        $objTransaction->afterBalance = $objUser->balance;
        $objTransaction->orderTime = date('Y-m-d H:i:s');
        $objTransaction->attchId = $objWithdraw->id;
        $objTransaction->remark = '';
        if(!$objTransaction->save()){
            Log::error('save objTransaction fail '.\serialize($objTransaction));
        }
        DB::commit();
        $eventDesc = '用户请求提款 '.$objUser->id.' '.$objUser->username.' 请求提款额度 '.($amountCent/100).'元';
        Log::info($eventDesc);
        Telegram::sendMessage($eventDesc);
        // Telegram::sendMessage('有新的提款请求，请处理，金额 '.($amountCent/100).' 元 '.(config('app.env')=='local'?'测试环境':'线上环境'));
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS];
    }
    // 增加提款请求
    /*
    public static function addWithdrawal($objUser, $amountCent){
        if($amountCent<=0){
            return ['code'=>ErrorCode::ERROR_INVALID_PARAM,'msg'=>ErrorMsg::ERROR_INVALID_PARAM];
        }
        DB::beginTransaction();
        $affectedRows = User::where('id', $objUser->id)->where('balance', '>=', $amountCent)->update([
            'balance' => DB::raw('balance-' . $amountCent),
            'freezeBalance' => DB::raw('freezeBalance+'.$amountCent)]);
        if($affectedRows!==1){
            DB::rollBack();
            return ['code'=> ErrorCode::ERROR_BALANCE_INSUFFICIENT,'msg'=> ErrorMsg::ERROR_BALANCE_INSUFFICIENT];
        }
        // TODO: insert withdrawal table
        DB::commit();
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS];
    }
    */
    // 增加扣款
    public static function addDeduction(&$objUser, $amountCent) {
        if($amountCent<=0){
            return ['code'=>ErrorCode::ERROR_INVALID_PARAM,'msg'=>ErrorMsg::ERROR_INVALID_PARAM];
        }
        DB::beginTransaction();
        $affectedRows = User::where('id', $objUser->id)->where('balance', '>=', $amountCent)->update([
            'balance' => DB::raw('balance-' . $amountCent),
            ]);
        if($affectedRows!==1){
            DB::rollBack();
            return ['code'=> ErrorCode::ERROR_BALANCE_INSUFFICIENT,'msg'=> ErrorMsg::ERROR_BALANCE_INSUFFICIENT];
        }
        DB::commit();
        $eventDesc = '给用户扣款 '.$objUser->id.' '.$objUser->username.' 扣款金额 '.($amountCent/100).'元';
        Log::info($eventDesc);
        Telegram::sendMessage($eventDesc);
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS];
    }
    // 转账  暂时屏蔽入口
    public static function transfer($outUser, $inUser, $amountCent){
        if($amountCent<=0){
            return ['code'=>ErrorCode::ERROR_INVALID_PARAM,'msg'=>ErrorMsg::ERROR_INVALID_PARAM];
        }
        DB::beginTransaction();
        $affectedRows = User::where('id', $outUser->id)->where('balance', '>=', $amountCent)->update([
            'balance' => DB::raw('balance-' . $amountCent),
        ]);
        if($affectedRows!==1){
            DB::rollBack();
            return ['code'=> ErrorCode::ERROR_BALANCE_INSUFFICIENT,'msg'=> ErrorMsg::ERROR_BALANCE_INSUFFICIENT];
        }
        $inUser->increment('balance', $amountCent);
        DB::commit();
        $eventDesc = '用户转帐 from '.$inUser->id.' '.$inUser->username.' to '.$outUser->id.' '.$outUser->username.' 额度 '.($amountCent/100).'元';
        Log::info($eventDesc);
        Telegram::sendMessage($eventDesc);
        return ['code'=> ErrorCode::SUCCESS,'msg'=> ErrorMsg::SUCCESS];
    }
}
