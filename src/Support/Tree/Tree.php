<?php

/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2022/01/18 14:30
 */

namespace LaravelHelpers\Support\Tree;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use JsonSerializable;
use LaravelHelpers\Exceptions\InvalidArgumentException;
use LaravelHelpers\Support\Batch;
use Traversable;

class Tree
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $parentKey = 'pid';

    /**
     * @var string
     */
    protected $pathKey = 'path';

    /**
     * 等级字段
     *
     * @var string
     */
    protected $gradeKey = 'grade';

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Collection
     */
    protected $tree;

    /**
     * 忽略
     *
     * @var array
     */
    protected $ignore = [];

    /**
     * 是否需要记录 path
     *
     * @var bool
     */
    protected $recordPath = false;

    /**
     * path 路径
     *
     * @var array
     */
    protected $paths = [];

    /**
     * 等级
     *
     * @var array
     */
    protected $grades = [];

    public function __construct()
    {
        $this->tree = collect([]);
    }

    /**
     * Creates a new Tree.
     *
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function make()
    {
        $collection = $this->collection;

        if (!$collection) {
            if (!$this->model) {
                throw new InvalidArgumentException('The model is not define.');
            }

            /** @var \Illuminate\Database\Eloquent\Collection|static[] $all */
            $collection = $this->model->newInstance()->all()->toBase();
        }

        $this->collection = $collection->groupBy($this->parentKey);

        // top node
        if ($this->addTopNode()) {
            return $collection;
        }

        if ($this->collection->isNotEmpty()) {
            foreach ($this->tree as $item) {
                $this->recursion($item);
            }
        }

        return $this->tree;
    }

    /**
     * 添加顶级节点
     *
     * @return bool|void
     */
    private function addTopNode()
    {
        $nodes = $this->collection->pull(0);

        if (!$nodes) {
            return true;
        }

        foreach ($nodes as $item) {
            if (in_array($item->{$this->primaryKey}, $this->ignore)) {
                continue;
            }

            $this->addNodePath($item);

            $node = new Node($this->getArrayableItems($item));
            $this->tree->push($node);
        }

        return false;
    }

    /**
     * 递归获取节点
     *
     * @param Node $item
     */
    protected function recursion(Node $item)
    {
        $primary = $item->{$this->primaryKey};

        if (in_array($primary, $this->ignore)) {
            return;
        }

        if ($this->collection->has($primary)) {
            $this->collection->pull($primary)->map(function ($temp) use ($item) {
                if (in_array($temp->{$this->primaryKey}, $this->ignore)) {
                    return true;
                }

                $this->addNodePath($temp, $item);

                $node = new Node($this->getArrayableItems($temp));
                $item->addChildren($node);

                $this->recursion($node);
            });
        }
    }

    /**
     * @param mixed|null $parent
     * @return string
     */
    public function getChildPath($parent = null)
    {
        $path = '';

        if ($parent) {
            $parentPath = $parent->{$this->pathKey} ? $parent->{$this->pathKey} . ',' : '';
            $path = $parentPath . $parent->{$this->primaryKey};
        }

        return $path;
    }

    /**
     * 修复 path
     */
    public function fixPath()
    {
        $this->recordPath = true;

        $this->make();

        $updateData = [];

        foreach ($this->paths as $id => $path) {
            $data = [
                $this->primaryKey => $id,
                $this->pathKey    => $path
            ];

            if ($this->grades) {
                $data[$this->gradeKey] = Arr::get($this->grades, $id, 1);
            }

            $updateData[] = $data;
        }


        // 进行批量修改
        if ($updateData) {
            Batch::update($this->model->newInstance(), $updateData, $this->primaryKey);
        }

        $this->recordPath = false;
    }

    /**
     * 添加节点 path
     *
     * @param $item
     * @param null $parent
     */
    protected function addNodePath($item, $parent = null)
    {
        if (!$this->recordPath) {
            return;
        }

        $path = '';

        if ($parent) {
            $parentPrimaryKey = $parent->{$this->primaryKey};

            $parentPath = $this->paths[$parentPrimaryKey] ?? '';
            $parentPath = $parentPath ? $parentPath . ',' : '';

            $path = $parentPath . $parentPrimaryKey;

            // 记录等级
            if ($this->gradeKey) {
                $grade = $parent->{$this->gradeKey} + 1;
                $this->grades[$item->{$this->primaryKey}] = $grade;
            }
        }

        $this->paths[$item->{$this->primaryKey}] = $path;
    }

    /**
     * @param Model $model
     * @return Tree
     */
    public function setModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param string $primaryKey
     * @return Tree
     */
    public function setPrimaryKey(string $primaryKey): static
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @param string $parentKey
     * @return Tree
     */
    public function setParentKey(string $parentKey): static
    {
        $this->parentKey = $parentKey;

        return $this;
    }

    /**
     * @param Collection $collection
     * @return Tree
     */
    public function setCollection(Collection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @param array|string|int $ignore
     */
    public function setIgnore($ignore): static
    {
        $this->ignore = Arr::wrap($ignore);

        return $this;
    }

    /**
     * @param string $pathKey
     * @return Tree
     */
    public function setPathKey(string $pathKey): static
    {
        $this->pathKey = $pathKey;

        return $this;
    }

    /**
     * @param string $gradeKey
     * @return Tree
     */
    public function setGradeKey(string $gradeKey): static
    {
        $this->gradeKey = $gradeKey;

        return $this;
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof Enumerable) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        } elseif ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }
}
