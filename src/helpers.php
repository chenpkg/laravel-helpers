<?php
/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2021/12/14 15:40
 */

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

if (!function_exists('scope_collection')) {
    /**
     * 调用查询作用域
     *
     * @param Builder $builder
     * @param string|array $scope
     * @param string|array|null $prepends
     * @return Builder
     * @example
     *     scope_collection($builder, ['name', 'age']);
     *     scope_collection($builder, ['name', 'age'], 'normal');
     *     scope_collection($builder, ['name', 'age'], ['normal', 'pretty']);
     *     scope_collection($builder, ['name', 'age'], ['normal', 'user' => 2, 'admin' => ['user', 'admin']]);
     */
    function scope_collection(Builder $builder, string|array $scope, string|array|null $prepends = null): Builder
    {
        if ($prepends) {
            $builder->scopes($prepends);
        }

        return $builder->scopes(collect($scope)->filter(
            fn($name) => filled(request($name))
        )->mapWithKeys(
            fn($value) => [Str::camel($value) => request($value)]
        )->all());
    }
}

if (!function_exists('dump_sql')) {
    /**
     * 打印 sql
     *
     * @param $builder
     * @return mixed
     */
    function dump_sql($builder): mixed
    {
        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        array_walk($bindings, function ($value) use (&$sql) {
            $value = is_string($value) ? var_export($value, true) : $value;
            $sql = preg_replace("/\?/", $value, $sql, 1);
        });

        return $sql;
    }
}

if (!function_exists('dd_sql')) {
    /**
     * 打印 sql
     *
     * @param $builder
     * @return void
     */
    function dd_sql($builder)
    {
        dd(dump_sql($builder));
    }
}