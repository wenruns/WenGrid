<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/28
 * Time: 10:50
 */

namespace Wenruns\Grid;


use Encore\Admin\Grid\Filter;

class WenFilter extends Filter
{

    /**
     * Execute the filter with conditions.
     *
     * @param bool $toArray
     *
     * @return array|Collection|mixed
     */
    public function execute($toArray = true, $exportOptions = [])
    {
        if (method_exists($this->model->eloquent(), 'paginate')) {
            $this->model->usePaginate(true);

            return $this->model->buildData($toArray);
        }
        $conditions = array_merge(
            $this->conditions(),
            $this->scopeConditions()
        );
        $conditions = array_merge($conditions, $exportOptions);
        return $this->model->addConditions($conditions)->buildData($toArray);
    }

}