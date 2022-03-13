<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $phone = $request->phone;

        // 可以设置测试环境验证码
        if (!app()->environment('production')){
            $code = '1234';
        } else {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1,9999),4,0,STR_PAD_LEFT);

            try {
                $result = $easySms->send($phone,[
                    'content'  => '您正在使用阿里云短信测试服务，体验验证码是：${code}，如非本人操作，请忽略本短信！',
                    'template' => 'SMS_154950909',
                    'data' => [
                        'code' => $code
                    ],
                ]);
            } catch (NoGatewayAvailableException $exception) {
                $message = $exception->getException('aliyun')->getMessage();
                abort(500, $message ?: '短信发送异常');
            }
        }

        $key = 'verificationCode_'.Str::random(15);
        $expiredAt = now()->addMinute(5);
        // 缓存验证码 5 分钟过期。
        \Cache::put($key,[
            'phone'=>$phone,
            'code'=>$code
        ],$expiredAt);

        return response()->json([
            'key'=>$key,
            'expired_at'=>$expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}
