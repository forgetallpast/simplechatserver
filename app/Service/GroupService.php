<?php
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
use App\Service\UserService;
class GroupService {
    public static function createGroup($groupName,$bulletin,$header,$groupType,$objCreateUser){
        $objGroup = new Group();
        $objGroup->groupName = $groupName;
        $objGroup->bulletin = $bulletin;
        $objGroup->header = $header;
        $objGroup->groupType = $groupType;
        $objGroup->ownerId = $objCreateUser->id;
        if(!$objGroup->save()){
            return [
                'code' => ErrorCode::ERROR_SERVER_ERROR,
                'msg' => ErrorMsg::ERROR_SERVER_ERROR
            ]; 
        }
        $joinResult = UserService::joinGroup($objCreateUser, $objGroup);
        if(!$joinResult){
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
}