<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Image;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * 用户注册
     * @param UserRequest $request
     * @return UserResource
     * @throws AuthenticationException
     */
    public function store(UserRequest $request)
    {
        $verifyData = \Cache::get($request->verification_key);

        if (!$verifyData) {
            abort(403, '验证码已失效');
        }

        // 可防止时序攻击的字符串比较
        if (!hash_equals(strtolower($verifyData['code']), strtolower($request->verification_code))) {
            // 返回401
            throw new AuthenticationException('验证码错误');
        }

        $user = User::create([
            'name'     => $request->name,
            'phone'    => $verifyData['phone'],
            'password' => $request->password,
        ]);

        // 清除验证码缓存
        \Cache::forget($request->verification_key);

        return (new UserResource($user))->showSensitiveFields();
    }

    /**
     * 获取单个用户信息
     * @param User $user
     * @param Request $request
     * @return UserResource
     */
    public function show(User $user, Request $request)
    {
        return new UserResource($user);
    }

    /**
     * 本人的用户信息
     * @param Request $request
     * @return UserResource
     */
    public function me(Request $request)
    {
        return (new UserResource($request->user()))->showSensitiveFields();
    }

    /**
     * 修改用户信息
     * @param UserRequest $request
     * @return UserResource
     */
    public function update(UserRequest $request)
    {
        $user = $request->user();

        $attributes = $request->only(['name', 'email', 'introduction']);

        // 当用户上传头像
        if ($request->avatar_image_id) {
            $image                = Image::find($request->avatar_image_id);
            $attributes['avatar'] = $image->path;
        }

        // 修改用户信息
        $user->update($attributes);

        return (new UserResource($user))->showSensitiveFields();
    }

    /**
     * 活跃用户列表
     * @param User $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function activedIndex(User $user)
    {
        UserResource::wrap('data');
        return UserResource::collection($user->getActiveUsers());
    }
}
