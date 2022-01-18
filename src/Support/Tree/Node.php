<?php

/**
 * Created by Cestbon.
 * Author Cestbon <734245503@qq.com>
 * Date 2022/01/18 14:30
 */

namespace LaravelHelpers\Support\Tree;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class Node extends Fluent
{
    /**
     * Convert the fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributes;

        if (Arr::exists($attributes, 'children')) {
            $attributes['children'] = $attributes['children'] instanceof Arrayable ?
                $attributes['children']->toArray() :
                $attributes['children'];
        }

        return $attributes;
    }

    /**
     * @param Node $node
     * @param string $childrenKey
     */
    public function addChildren(Node $node, string $childrenKey = 'children')
    {
        if (!Arr::exists($this->attributes, $childrenKey)) {
            $this->attributes[$childrenKey] = collect([]);
        }

        $this->attributes[$childrenKey]->push($node);
    }
}
