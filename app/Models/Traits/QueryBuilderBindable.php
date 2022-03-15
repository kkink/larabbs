<?php

namespace App\Models\Traits;

Trait QueryBuilderBindable
{
    public function resolveRouteBinding($value)
    {
        // 判断类或对象中的属性是否存在
        $queryClass = property_exists($this, 'queryClass')
            ? $this->queryClass
            : '\\App\\Http\\Queries\\'.class_basename(self::class).'Query';

        // 如果类不存在
        if (!class_exists($queryClass)) {
            return parent::resolveRouteBinding($value);
        }

        return (new $queryClass($this))
            ->where($this->getRouteKeyName(), $value)
            ->first();
    }
}
