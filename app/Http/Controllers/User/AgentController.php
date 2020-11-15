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
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Service\UserService;
use App\Models\AgentDayReport;
use App\Models\AgentUserLevel;
use App\Models\AgentTalk;
class AgentController extends \App\Http\Controllers\Controller{
    public function agentUsers(Request $request){
        $objUser = auth()->user();
        if(empty($objUser->refCode)){
            return [
                'code' => ErrorCode::ERROR_NOT_AGENT,
                'msg' => ErrorMsg::ERROR_NOT_AGENT
            ];
        }
        $arrUserLevels = AgentUserLevel::getAgentlUsers($objUser->id);
        $arrData = [];
        foreach ($arrUserLevels as $objUserLevel){
            $level = (int)$objUserLevel->level;
            if(!isset($arrData[$level])){
                $arrData[$level] = 0;
            }
            ++$arrData[$level];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrData
        ];
    }
    
    public function agentTalks(Request $request){
        $objUser = auth()->user();
        if(empty($objUser->refCode)){
            return [
                'code' => ErrorCode::ERROR_NOT_AGENT,
                'msg' => ErrorMsg::ERROR_NOT_AGENT
            ];
        }
        $arrTalks = AgentTalk::all();
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrTalks
        ];
    }
    public function agentLink(Request $request){
        $objUser = auth()->user();
        if(empty($objUser->refCode)){
            return [
                'code' => ErrorCode::ERROR_NOT_AGENT,
                'msg' => ErrorMsg::ERROR_NOT_AGENT
            ];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => ''
        ];
    }
}