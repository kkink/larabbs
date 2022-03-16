<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * 显示敏感字段标识
     * @var bool
     */
    protected $showSensitiveFields = false;

    /**
     * Transform the resource into an array.
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 如果显示敏感信息
        if (!$this->showSensitiveFields) {
            $this->resource->makeHidden(['phone', 'email']);
        }

        $data = parent::toArray($request);

        // 增加数据
        $data['bound_phone']  = $this->resource->phone ? true : false;// 是否绑定手机号
        $data['bound_wechat'] = $this->resource->weixin_unionid || $this->resource->weixin_openid ? true : false;// 是否绑定微信
        $data['roles']        = RoleResource::collection($this->whenLoaded('roles'));// 用户权限列表

        return $data;
    }

    /**
     * 显示敏感信息
     * @return $this
     */
    public function showSensitiveFields()
    {
        $this->showSensitiveFields = true;

        return $this;
    }


}
