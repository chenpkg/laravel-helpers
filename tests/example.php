<?php

/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2021/12/14 15:44
 */

require __DIR__ . '/../vendor/autoload.php';

use Chenpkg\LaravelHelpers\Support\Batch;
use Chenpkg\LaravelHelpers\Traits\HasScopeCollection;

/**
 * 用户模型如下
 */
class User extends \Illuminate\Database\Eloquent\Model
{
    // 引入快速构建查询作用域助手类
    use HasScopeCollection;

    // 用户表有如下字段
    protected $fillable = [
        'name', 'age', 'phone', 'city', 'address'
    ];

    // 查询作用域
    public function scopeName($query, $value)
    {
        return $query->where('name', $value);
    }

    public function scopeAge($query, $value)
    {
        return $query->where('age', $value);
    }

    public function scopeAddress($query, $value)
    {
        return $query->where('address', $value);
    }
}

$scopes = ['name', 'age', 'phone', 'city', 'address'];

// 快速构建查询作用域
// 现在有多个查询条件，根据 name, age, phone, city, address 查询
// scope_collection 会根据第二个参数自动获取 request 值再去触发模型的查询作用域，最后再返回 \Illuminate\Database\Eloquent\Builder
// 第三个参数传入之后会直接触发查询作用域
$builder = scope_collection(User::query(), $scopes);

// 如果使用 HasScopeCollection trait，则可使用如下格式
$builder = User::resolveScope($scopes);

// 打印 sql
dd_sql($builder);
// 或者 dd(dump_sql($builder))

// 获取数据
$result = $builder->get();


// 批量修改数据
// 来源于 https://github.com/mavinoo/laravelBatch
$update = [
    [
        'id'   => 1,
        'name' => '李四'
    ],
    [
        'id'   => 5,
        'name' => '小红'
    ],
    [
        'id'   => 5,
        'name' => '李雷雷'
    ]
];

Batch::update(new User(), $update, 'id');

// 分页添加自定义数据
$result = User::where('age', '>', 18)->metaPaginate();

$result->addMeta('count', 1);

$result->setMeta(['count' => 1]);
