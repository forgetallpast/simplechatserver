<?php
namespace App\Library;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
class Message
{
    public static function sendSmsVerifyCode($phone,$verifyCode){
        $client = new Client(['verify' => false ]);
        //$client->setDefaultOption('verify', false);
        $url = 'http://utf8.api.smschinese.cn/?Uid='.urlencode(config('app.SMS_ACCOUNT'))
        .'&Key='.urlencode(config('app.SMS_KEY'))
        .'&smsMob='.urlencode($phone).'&smsText='.urlencode($verifyCode);
        $res = $client->get($url);
        $statusCode = $res->getStatusCode();
        if($statusCode==200){
            $intRet = $res->getBody()->getContents();
            Log::info($intRet);
            if($intRet > 0){
                Log::info('send mobile message '.$url.' success '.$phone.' '.$verifyCode);
                return true;
            }else{
                Log::warning('send mobile message '.$url.' fail '.$phone.' '.$verifyCode.' '.$intRet);
                return false;
            }
        }else{
            Log::warning('send mobile message '.$url.' fail '.$phone.' '.$verifyCode.' '.$intRet);
            return false;
        }
    }
}