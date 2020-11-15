<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix'=>'guest','namespace'=>'Guest'], function($router){
    $router->match(array('GET','POST'), 'register', 'GuestController@register');
    $router->match(array('GET','POST'), 'login', 'GuestController@login')->name('login');
    $router->post('send_sms_code', 'GuestController@sendSmsCode');
    $router->post('send_modify_password_sms_code', 'GuestController@sendModifyPasswordSmsCode');
    $router->post('modify_password', 'GuestController@modifyPassword');
    // $router->post('/guest/register', 'GuestController@register');
    // $router->post('/guest/login', 'GuestController@login');
    $router->match(array('GET','POST'), 'admin_login', 'GuestController@adminLogin')->name('adminLogin');
    $router->post('try_play', 'GuestController@tryPlay');
});


Route::group(['prefix'=>'user','namespace'=>'User', 'middleware'=>'auth:user'], function($router){
    $router->post('info', 'UserController@info');
    $router->post('contacts', 'UserController@contacts');
    $router->post('last_msg', 'UserController@lastMsg');
    $router->post('contacts', 'UserController@contacts');
    // 某个对话的记录信息
    $router->post('message_history', 'MessageController@chatHistory');
    $router->post('send_message', 'MessageController@sendMessage');
    $router->post('bind_socket', 'UserController@bindSocket');
    // 微信首页的拉取未读消息的接口
    $router->post('last_message', 'MessageController@lastMessage');
    // 清除某个chatId的未读消息数目
    $router->post('clear_unread_count', 'MessageController@clearUnreadMsgCount');
    // 发送图片
    $router->post('send_image', 'MessageController@sendImage');
    // 查看红包的相关信息
    $router->post('big_package_info', 'RedPackageController@bigPackageInfo');
    // 抢红包
    $router->post('grab_redpackage', 'RedPackageController@grabRedPackage');
    // 抢红包结果信息
    $router->post('red_package_result', 'RedPackageController@redPackageResult');
    // 拉取用户余额
    $router->post('wallet', 'UserController@walletInfo');
    // 更改用户昵称
    $router->post('change_nickname', 'UserController@changeNickname');
    // 更改用户头像
    $router->post('change_header', 'UserController@changeHeader');
    // 试玩用户绑定手机号密码
    $router->post('try_player_bind_telephone', 'UserController@tryPlayerBind');
    // 修改密码
    $router->post('change_password', 'UserController@changePassword');
    // 总体交易记录
    $router->post('transaction_list', 'RecodeController@transactionList');
    // 不同类型的交易记录
    $router->post('transaction_type_list', 'RecodeController@transactionTypeList');
    // 优惠列表
    $router->post('promo_list', 'PromoController@promoList');
    // 代理数据
    $router->post('agent_data', 'AgentController@agentData');
    // 代理下级用户
    $router->post('agent_users', 'AgentController@agentUsers');
    // 代理话术
    $router->post('agent_talks', 'AgentController@agentTalks');
    // 代理链接
    $router->post('agent_link', 'AgentController@agentLink');
    // 用户提款，如银行卡等信息
    $router->post('withdraw_info', 'MoneyController@withdrawInfo');
    // 更改用户提款密码
    $router->post('change_withdraw_password', 'MoneyController@changeWithdrawPassword');
    // 用户增加出款银行卡
    $router->post('add_bankcard','MoneyController@addBankcard');
    // 用户申请提款
    $router->post('apply_withdraw', 'MoneyController@applyWithdraw');
    // 代理建群
    $router->post('create_group', 'GroupController@createGroup');
    // 群基本信息
    $router->post('chat_group_info', 'GroupController@groupInfoByChat');
    // 更改公告
    $router->post('change_bulletin', 'GroupController@changeBulletin');
    // 群主能够增加到群里的所有用户(是群主的好友，但是还不在群里的那些)
    $router->post('users_can_add', 'GroupController@usersCanAdd');
    // 增加群成员
    $router->post('add_group_members', 'GroupController@addGroupMembers');
    // 列出群成员
    $router->post('group_members', 'GroupController@groupMembers');
    // 与我私聊的另外一个用户的个人信息
    $router->post('other_user_info', 'UserController@otherUserInfo');
    // 群主更改禁言状态
    $router->post('toggle_disable_talk', 'GroupController@toggleDisableTalk');
    // 用户删除绑定的银行卡
    $router->post('delete_bankcard', 'MoneyController@deleteBankcard');
    // 用户发起支付
    $router->post('get_deposit_url', 'PayController@requestPay');
    // 支付回调
    $router->post('fx_pay_notify', 'PayController@fxPayNotify');
});

Route::group(['prefix'=>'notify','namespace'=>'User'], function($router){
    // 支付回调
    $router->post('fx_pay_notify', 'PayController@fxPayNotify');
});

Route::group(['prefix'=>'staff','namespace'=>'Staff', 'middleware'=>'auth:staff'], function($router){
    $router->post('user/list', 'ListController@userList');
    $router->post('user/info', 'UserController@info');
    $router->post('change_level', 'UserController@changeLevel');
    $router->post('mark_cheater', 'UserController@changeCheaterMark');
    $router->post('report/day_report', 'ReportController@dayReport');
    $router->post('report/user_day_deposit_report', 'ReportController@userDayDepositReport');
    $router->post('report/user_day_withdrawal_report', 'ReportController@userDayWithdrawalReport');
    $router->post('report/user_day_commission_report', 'ReportController@userDayCommissionReport');
    $router->post('report/user_day_deduction_report', 'ReportController@userDayDeductionReport');
    // 手动存款
    $router->post('operate/change_balance', 'OperateController@changeBalance');
    // 手动加佣金
    $router->post('operate/change_commission', 'OperateController@changeCommission');
    // 增加提款请求
    $router->post('operate/add_withdrawal', 'OperateController@addWithdrawal');
    // 手动增加扣款
    $router->post('operate/add_deduction', 'OperateController@deduction');
    // 转账
    $router->post('operate/transfer', 'OperateController@transfer');
    // 增加优惠
    $router->post('promo/add_promo', 'PromoController@addPromo');
    // 优惠列表
    $router->post('promo/list', 'PromoController@promoList');
    // 所有客服人员的列表
    $router->post('user/all_services','UserController@allServices');
    // 更改优惠状态
    $router->post('promo/change_status', 'PromoController@changeStatus');
    // 代理话术列表
    $router->post('agent_talks', 'AgentController@agentTalks');
    // 增加代理话术
    $router->post('add_agent_talk','AgentController@addAgentTalk');
    // 删除话术
    $router->post('delete_talk', 'AgentController@deleteTalk');
    // 代理数据
    $router->post('agent_data', 'AgentController@agentData');
    // 设置用户为代理
    $router->post('set_as_agent', 'AgentController@setAsAgent');
    // 用户银行卡列表
    $router->post('bankcards_info', 'UserController@bankcards');
    // 用户提款申请
    $router->post('withdrawal_apply', 'UserController@withdrawalApply');
    // 处理用户提款申请
    $router->post('process_withdraw_apply', 'OperateController@processWithdrawApply');
    // 三方支付列表
    $router->post('deposit_3rd', 'UserController@deposit3rd');
});









