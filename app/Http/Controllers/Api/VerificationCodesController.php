<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

/**
 * 短信验证
 */
class VerificationCodesController extends Controller
{
    /**
     * 发送短信验证码
     * @param VerificationCodeRequest $request
     * @param EasySms $easySms
     * @return \Illuminate\Http\JsonResponse|object
     * @throws AuthenticationException
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     */
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        // 从缓存中获取图片验证码的相关信息
        $captchaData = \Cache::get($request->captcha_key);

        // 图片验证码校验
        if (!$captchaData) {
            abort(403, '图片验证码已失效');
        }

        // 验证图片验证码
        if (!hash_equals($captchaData['code'], $request->captcha_code)) {
            // 验证码错误就清除缓存
            \Cache::forget($request->captcha_key);
            throw new AuthenticationException('验证码错误');
        }

        $phone = $captchaData['phone'];

        // 可以设置测试环境验证码
        if (!app()->environment('production')) {
            $code = '1234';
        } else {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

            // 发送短信
            try {
                $result = $easySms->send($phone, [
                    'content'  => '您正在使用阿里云短信测试服务，体验验证码是：${code}，如非本人操作，请忽略本短信！',
                    'template' => 'SMS_154950909',
                    'data'     => [
                        'code' => $code
                    ],
                ]);
            } catch (NoGatewayAvailableException $exception) {
                $message = $exception->getException('aliyun')->getMessage();
                abort(500, $message ?: '短信发送异常');
            }
        }

        // 存入短信验证码相关信息
        $key       = 'verificationCode_' . Str::random(15);
        $expiredAt = now()->addMinute(5);

        // 缓存验证码 5 分钟过期。
        \Cache::put($key, [
            'phone' => $phone,
            'code'  => $code
        ], $expiredAt);

        // 清除图片验证码缓存
        \Cache::forget($request->captcha_key);

        // 返回数据
        return response()->json([
            'key'        => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}
