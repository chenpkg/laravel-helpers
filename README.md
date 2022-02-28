<h1 align="center"> laravel-helpers </h1>

<p align="center"> laravel helpers.</p>

## 功能

-   Scope 模型查询作用域构建
-   Batch 模型批量修改
-   Tree 模型无限极分类树
-   MetaPaginator 模型分页自定义添加数据

## 安装

```shell
$ composer require chenpkg/laravel-helpers
```

## 使用

[Scope 使用示例](https://github.com/chenpkg/laravel-helpers/tree/master/tests/example.php)

### Batch 批量修改

> 功能来源于 [laravelBatch](https://github.com/mavinoo/laravelBatch)

```php
use LaravelHelpers\Support\Batch;

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
```

[Tree 使用示例](https://github.com/chenpkg/laravel-helpers/tree/master/src/Support/Tree/README.MD)

### MetaPaginator

```php
// 分页添加自定义数据
$result = User::where('age', '>', 18)->metaPaginate();

$result->addMeta('count', 1)->addMeta('total', 1000);
// or
$result->setMeta([
  'count' => 1,
  'total' => 1000
]);

```

## License

MIT
