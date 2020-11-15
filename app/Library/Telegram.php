<?php
namespace App\Library;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
class Telegram
{
    public static function sendMessage($htmlMessage){
        $htmlMessage = $htmlMessage.' '.(config('app.env')=='local'?'测试环境':'线上环境');
        $botToken = config('app.TELEGRAM_BOT_TOKEN');
        $chatId = config('app.TELEGRAM_CHAT_ID');
        if(empty($botToken)||empty($chatId)){
            Log::error('no botToken or chatId to send message');
            return false;
        }
        $client = new Client(['verify' => false ]);
        //$client->setDefaultOption('verify', false);
        $res = $client->post('https://api.telegram.org/bot'.$botToken.'/sendMessage',
            ['json'=>[
                'chat_id'=>$chatId,
                'text'=>$htmlMessage,
                'disable_web_page_preview'=>true
            ]]);
        $statusCode = $res->getStatusCode();
        if($statusCode==200){
            $retJson = $res->getBody();
            $arrRet = json_decode($retJson, true);
            if(isset($arrRet['ok'])&&$arrRet['ok']){
                Log::info('send message '.$htmlMessage.' success');
                return true;
            }else{
                Log::warning('send message '.$htmlMessage.' fail');
                return false;
            }
        }else{
            Log::warning('send message '.$htmlMessage.' fail');
            return false;
        }
    }
}