<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;

class Authenticate extends Middleware
{
}
