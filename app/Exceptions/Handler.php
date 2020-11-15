<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
            //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $exception) {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception) {
        if ($exception instanceof AuthenticationException) {
            return response(['code' => ErrorCode::ERROR_TOKEN_EXPIRED, 'msg' => ErrorMsg::ERROR_TOKEN_EXPIRED])->send();
        } else {
            Log::error($exception->getTraceAsString());
            return response(['code' => ErrorCode::ERROR_SERVER_ERROR, 'msg' => ErrorMsg::ERROR_SERVER_ERROR])->send();
        }
    }

}
