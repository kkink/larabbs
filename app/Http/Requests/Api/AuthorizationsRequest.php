<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizationsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // 两种组合的参数
        // 组合一：只有 code
        // 组合二：access_code 和 openid
        $rules = [
            'code'         => 'required_without:access_token|string',
            'access_token' => 'required_without:code|string',
        ];

        if ($this->social_type === 'wechat' && !$this->code) {
            $rules['openid'] = 'required|string';
        }
        return $rules;
    }
}
