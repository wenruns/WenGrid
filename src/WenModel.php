<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/28
 * Time: 19:39
 */

namespace Wen\Grid;


use Encore\Admin\Grid\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class WenModel extends Model
{

    /**
     * @param bool $toArray
     * @return array|\Illuminate\Support\Collection|mixed
     * @throws \Exception
     */
    public function buildData($toArray = true)
    {
        if (empty($this->data)) {
            $collection = $this->get();
            if ($this->collectionCallback) {
                $collection = call_user_func($this->collectionCallback, $collection);
            }
            if ($toArray) {
                $this->data = $collection->toArray();
            } else {
                $this->data = $collection;
            }
        }
        return $this->data;
    }

    /**
     * @throws \Exception
     *
     * @return Collection
     */
    protected function get()
    {
        if ($this->model instanceof LengthAwarePaginator) {
            return $this->model;
        }
        if ($this->relation) {
            $this->model = $this->relation->getQuery();
        }

        $this->setSort();
        $this->setPaginate();
        $this->queries->unique()->each(function ($query) {
//            dump($query);
            $this->model = call_user_func_array([$this->model, $query['method']], $query['arguments']);
        });
//        dd($this->grid->model()->getQueryBuilder()->toSql());
//        dd(123);
        if ($this->model instanceof Collection) {
            return $this->model;
        }

        if ($this->model instanceof LengthAwarePaginator) {
            $this->handleInvalidPage($this->model);
            return $this->model->getCollection();
        }
        throw new \Exception('Grid query error');
    }


}