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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1')
    ->namespace('Api')
    ->name('api.v1.')
    ->group(function () {
        Route::get('smstest', function () {
            $sms = app('easysms');
            try {
                $sms->send(13929410175, [
                    'content'  => '您正在使用阿里云短信测试服务，体验验证码是：${code}，如非本人操作，请忽略本短信！',
                    'template' => 'SMS_154950909',
                    'data'     => [
                        'code' => 1234
                    ],
                ]);
            } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
                $message = $exception->getException('aliyun')->getMessage();
                dd($message);
            }
        });

        // 登录相关路由的频次限制
        Route::middleware('throttle:' . config('api.rate_limits.sign'))
            ->group(function () {
                // 图片验证码
                Route::post('captchas', 'CaptchasController@store')->name('captchas.store');

                // 短信验证码
                Route::post('verificationCodes', 'VerificationCodesController@store')->name('verificationCodes.store');

                // 用户注册
                Route::post('users', 'UserController@store')->name('users.store');

                // 第三方登录
                Route::post('socials/{social_type}/authorizations', 'AuthorizationsController@socialStore')
                    ->where('social_type', 'wechat')
                    ->name('socials.authorizations.store');

                // 登录
                Route::post('authorizations', 'AuthorizationsController@store')->name('authorizations.store');

                // 刷新 token
                Route::put('authorizations/current', 'AuthorizationsController@update')->name('authorizations.update');

                // 删除 token
                Route::delete('authorizations/current', 'AuthorizationsController@destroy')->name('authorizations.destroy');

            });

        // 需要频次限制的路由，默认 1 分钟 60 次
        Route::middleware('throttle:' . config('api.rate_limits.access'))
            ->group(function () {
                // 游客可以访问的接口
                // 某个用户的详情
                Route::get('users/{user}', 'UserController@show')->name('users.show');

                // 分类列表
                Route::get('categories', 'CategoriesController@index')->name('categories.index');

                // 话题列表，详情
                Route::resource('topics', 'TopicsController')->only([
                    'index', 'show'
                ]);

                // 登录后可以访问的接口
                Route::middleware('auth:api')->group(function () {
                    // 当前登录用户信息
                    Route::get('user', 'UserController@me')->name('user.show');

                    // 编辑登录用户信息
                    Route::patch('user', 'UserController@update')->name('user.update');

                    // 上传图片
                    Route::post('images', 'ImagesController@store')->name('images.store');

                    // 发布话题，修改话题，删除话题
                    Route::resource('topics', 'TopicsController')->only([
                        'store', 'update', 'destroy'
                    ]);
                });
            });


    });

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::get('version', function () {
        return 'this is version v2';
    })->name('version');
});
