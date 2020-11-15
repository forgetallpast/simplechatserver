<?php

namespace App\Models;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class AgentTalk extends Model {
    protected $table = 'agent_talk';
    public $timestamps = false;
}