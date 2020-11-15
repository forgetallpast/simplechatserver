<?php
namespace App\Http\Controllers\User;
use Illuminate\Http\Request;
use App\Models\Promo;
use App\Library\ErrorCode;
use App\Library\ErrorMsg;
use App\Models\User;
use App\Library\Image;
class PromoController extends \App\Http\Controllers\Controller{
    public function promoList(Request $request){
        $arrAllPromos = Promo::getAllActiveDesc();
        $arrData = [];
        foreach ($arrAllPromos as $objPromo){
            $objSender = User::getById($objPromo->senderId);
            $arrData[] = [
                'html'=>$objPromo->html,
                'nickname'=>$objSender->nickname,
                'header' => Image::toHeaderUrl($objSender->header)
            ];
        }
        return [
            'code' => ErrorCode::SUCCESS,
            'msg' => ErrorMsg::SUCCESS,
            'data' => $arrData
        ];
    }
}