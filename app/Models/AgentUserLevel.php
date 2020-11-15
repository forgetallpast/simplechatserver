<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class AgentUserLevel extends Model {
    protected $table = 'agent_user_level';
    public $timestamps = false;
    public static function getAgentlUsers($agentId){
        return self::where('agentId',$agentId)->get();
    }
}