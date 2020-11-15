<?php
namespace App\Library;
 
use Monolog\Formatter\LineFormatter;
 
class CustomFormatter extends LineFormatter
{
    public function __construct($format = null, $dateFormat = 'Y-m-d H:i:s', $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        $logId = config('logid');
        if(empty($logId)){
            $logId = date('YmdHis').rand(100000,999999);
            config(['logid'=>$logId]);
        }
        // 默认格式："[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $format = sprintf("%s[%s][auth|%s][%s] %s", "[%datetime%]", $logId, auth()->id() ?? 0, "%channel%.%level_name%","%message% %context% %extra%\n");
        $ignoreEmptyContextAndExtra = true;
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }
}