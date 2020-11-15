<?php
namespace App\Http\Controllers\User;
use Illuminate\Http\Request;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use Illuminate\Support\Facades\Log;
use App\Models\UserBankcard;
use App\Service\UserService;
use App\Models\User;
class MoneyController extends \App\Http\Controllers\Controller{
    // 提款配置
    public function withdrawInfo(Request $request){
        $arrBanks = config('banks.code');
        $objUser = auth()->user();
        $hasWithdrawPassword = !empty($objUser->withdrawPassword);
        $arrBankcards = UserBankcard::getActiveUserBankcards($objUser->id);
        $arrRetCards = [];
        foreach ($arrBankcards as $objBankcard){
            $arrRetCards[] = [
                'id' => $objBankcard->id,
                'typeCode' => $objBankcard->typeCode,
                'bankName' => $arrBanks[$objBankcard->typeCode],
                'accountNumber' => '**** '.substr($objBankcard->accountNumber,-4)
            ];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => [
                'hasWithdrawPassword' => $hasWithdrawPassword,
                'bankcards' => $arrRetCards,
                'balance' => $objUser->balance
            ]
        ];
    }
    // 更改用户提款密码
    public function changeWithdrawPassword(Request $request){
        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');
        if (preg_match('/^[0-9]+$/', $newPassword) || strlen($newPassword) < 6 || strlen($newPassword) > 20) {
            return [
                'code' => ErrorCode::ERROR_INVALID_PASSWORD,
                'msg' => ErrorMsg::ERROR_INVALID_PASSWORD
            ];
        }
        $objUser = auth()->user();
        if($objUser->userType == User::TYPE_GUEST_PLAYER){
            return [
                'code' => ErrorCode::ERROR_GUEST_NO_WITHDRAW,
                'msg' => ErrorMsg::ERROR_GUEST_NO_WITHDRAW
            ];
        }
        if(!empty($objUser->withdrawPassword) && $oldPassword!=$objUser->withdrawPassword){
            return [
                'code' => ErrorCode::ERROR_WRONG_PASSWORD,
                'msg' => ErrorMsg::ERROR_WRONG_PASSWORD
            ];
        }
        $objUser->withdrawPassword = $newPassword;
        if(!$objUser->save()){
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }else {
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }
    // 用户增加提款银行卡
    public function addBankcard(Request $request){
        $objUser = auth()->user();
        $objUserBankcard = new UserBankcard();
        $objUserBankcard->userId = $objUser->id;
        $objUserBankcard->typeCode = $request->input('bankCode');
        $objUserBankcard->realName = $request->input('realName');
        $objUserBankcard->accountNumber = $request->input('accountNumber');
        $objUserBankcard->city = $request->input('city');
        $objUserBankcard->branchName = $request->input('branchName');
        if(!$objUserBankcard->save()){
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }else{
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }
    // 用户删除提款银行卡
    public function deleteBankcard(Request $request){
        $objUser = auth()->user();
        $bankcardId = $request->input('bankcardId');
        $objUserBankcard = UserBankcard::getBankcardById($bankcardId);
        if(empty($objUserBankcard)||$objUserBankcard->userId!=$objUser->id){
            return [
                'code' => ErrorCode::ERROR_NO_SUCH_BANKCARD,
                'msg' => ErrorMsg::ERROR_NO_SUCH_BANKCARD
            ];
        }
        $objUserBankcard -> status = UserBankcard::STATUS_DELETED;
        if(!$objUserBankcard->save()){
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ];
        }else{
            return [
                'code' => ErrorCode::SUCCESS,
                'msg' => ErrorMsg::SUCCESS
            ];
        }
    }
    // 用户申请提款
    public function applyWithdraw(Request $request){
        $objUser = auth()->user();
        $bankcardId = (int)$request->input('bankcardId');
        $amount = $request->input('amount');
        $amountCent = (int)($amount*100);
        $objBankcard = UserBankcard::find($bankcardId);
        if(empty($objBankcard)){
            return [
                'code' => ErrorCode::ERROR_NO_SUCH_BANKCARD,
                'msg' => ErrorMsg::ERROR_NO_SUCH_BANKCARD
            ];
        }
        $arrResult = UserService::addWithdrawal($objUser, $objBankcard, $amountCent);
        return $arrResult;
    }
}







