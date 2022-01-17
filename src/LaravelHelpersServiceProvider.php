<?php

namespace Chenpkg\LaravelHelpers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Chenpkg\LaravelHelpers\Support\MetaPaginator;

class LaravelHelpersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bindMacro();
    }

    /**
     * Bind Macro
     *
     * @return void
     */
    protected function bindMacro()
    {
        // 添加自定义数据分页方法
        Builder::macro('metaPaginate', function (
            $perPage = null,
            $columns = ['*'],
            $pageName = 'page',
            $page = null,
            $meta = []
        ) {
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $perPage = $perPage ?: $this->model->getPerPage();

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $perPage)->get($columns)
                : $this->model->newCollection();

            return new MetaPaginator($results, $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'meta' => $meta,
            ]);
        });
    }
}
