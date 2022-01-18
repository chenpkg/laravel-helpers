<?php

/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2021/12/14 15:52
 */

namespace LaravelHelpers\Traits;

trait HasScopeCollection
{
    /**
     * @param string|array      $scope
     * @param string|array|null $prepends
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function resolveScope(string|array $scope, string|array|null $prepends = null)
    {
        return scope_collection(static::query(), $scope, $prepends);
    }
}
