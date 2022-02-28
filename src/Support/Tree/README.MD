### 分类树操作

```php
use LaravelHelpers\Support\Tree\Tree;

$tree = Tree::create();

// 设置主键，默认为 id
$tree->setPrimaryKey('id');

// 设置父 id, 默认为 pid
$tree->setParentKey('pid');

// 设置 path，默认为 path
$tree->setPathKey('path');

// 设置等级字段
$tree->setGradeKey('grade');

// 获取子 path
$tree->getChildPath($parent);

// 设置集合数据
$collection = Category::where('grade', 1)->get();
$tree->setCollection($collection);

// 设置模型
$tree->setModel(new User());

// 以下操作需要设置模型或集合

// 生成分类树
$tree->make();

// 修正分类 path
$tree->fixPath();
```

### 使用

```php
Tree::create()->setModel(new User())->make();
// or
$collection = Category::where('grade', 1)->get();
Tree::create()->setCollection($collection)->make();
```
