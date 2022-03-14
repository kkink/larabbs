<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\AuthorizationsRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthorizationsController extends Controller
{
    /**
     * 登录
     * @param AuthorizationRequest $request
     * @return \Illuminate\Http\JsonResponse|object
     * @throws AuthenticationException
     */
    public function store(AuthorizationRequest $request)
    {
        // 获取参数
        $username = $request->username;

        // 查询用户名中是否有邮箱
        // 用户名可以是邮箱也可以是手机号
        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;

        $credentials['password'] = $request->password;

        // 验证用户名和密码是否正确，正确则生成 token
        if (!$token = \Auth::guard('api')->attempt($credentials)) {
            throw new AuthenticationException('用户名或密码错误');
        }

        // 返回数据
        return $this->respondWithToken($token)->setStatusCode(201);
    }

    /**
     * 第三方登录
     * @param $type
     * @param AuthorizationsRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthenticationException
     */
    public function socialStore($type, AuthorizationsRequest $request)
    {
        $driver = \Socialite::create($type);

        try {
            if ($code = $request->code) {
                $oauthUser = $driver->userFromCode($code);
            } else {
                // 微信需要增加 openid
                if ($type == 'wechat') {
                    $driver->withOpenid($request->openid);
                }

                $oauthUser = $driver->userFromToken($request->access_token);
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }

        if (!$oauthUser->getId()) {
            throw new AuthenticationException('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'wechat':
                $unionid = $oauthUser->getRaw()['unionid'] ?? null;

                if ($unionid) {
                    $user = User::where('weixin_unionid', $unionid)->first();
                } else {
                    $user = User::where('weixin_openid', $oauthUser->getId())->first();
                }

                // 没有用户，则默认创建一个用户
                if (!$user) {
                    $user = User::create([
                        'name'           => $oauthUser->getNickname(),
                        'avatar'         => $oauthUser->getAvatar(),
                        'weixin_openid'  => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }

                break;
        }
        // 第三方登录可以自己登录一下
        $token = auth('api')->login($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }

    /**
     * 返回token信息
     * @param $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithToken($token)
    {
        // 返回数据
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => \Auth::guard('api')->factory()->getTTl() * 60
        ]);
    }

    /**
     * 刷新 token
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $token = auth('api')->refresh();
        return $this->respondWithToken($token);
    }

    /**
     * 删除 token
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function destroy()
    {
        auth('api')->logout();
        return response(null, 204);
    }
}
