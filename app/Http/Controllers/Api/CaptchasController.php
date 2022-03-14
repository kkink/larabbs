<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CaptchaRequest;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptchasController extends Controller
{
    public function store(CaptchaRequest $request, CaptchaBuilder $captchaBuilder)
    {
        // 获取参数
        $key   = 'captcha-' . Str::random(15);// 图片验证码 key
        $phone = $request->phone;

        // 获取图片验证码
        $captcha   = $captchaBuilder->build();
        $expiredAt = now()->addMinutes(2);// 设置 2 分钟后过期

        // 放入缓存
        \Cache::put($key, [
            'phone' => $phone,//提前存入手机号
            'code'  => $captcha->getPhrase()//获取验证码文本
        ], $expiredAt);

        // 返回数据
        $result = [
            'captcha_key'           => $key,// 图片验证码 key
            'expired_at'            => $expiredAt,// 过期事件
            'captcha_image_content' => $captcha->inline()//获取base64图片
        ];
        return response()->json($result)->setStatusCode(201);
    }
}
