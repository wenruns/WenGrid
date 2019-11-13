<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 15:27
 */

namespace vendor\WenGrid;


use Closure;
use Encore\Admin\Grid;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;

class WenGrid extends Grid
{

    protected $fileName = null;

    protected $body = [];

    protected $head = [];


    public function __construct(Eloquent $model, Closure $builder = null)
    {
        $this->keyName = $model->getKeyName();
        $this->model = new WenModel($model);

        $this->columns = new Collection();
        $this->rows = new Collection();
        $this->builder = $builder;

        $this->model()->setGrid($this);

        $this->setupTools();
        $this->setupFilter();

        $this->handleExportRequest();

        if (static::$initCallback instanceof Closure) {
            call_user_func(static::$initCallback, $this);
        }
    }


    public function renderExportButton()
    {
        return (new WenExportButton($this))->render();
    }

    /**
     * @param bool $forceExport
     * @return bool|mixed|void
     */
    protected function handleExportRequest($forceExport = false)
    {
        //dump('handleExportRequest---');
        if (!$scope = request(Grid\Exporter::$queryName)) {
            return;
        }

        // clear output buffer.
        if (ob_get_length()) {
            ob_end_clean();
        }
        $this->model()->usePaginate(false);
        if ($this->builder) {
            call_user_func($this->builder, $this);

            return $this->getExporter($scope)->export();
        }

        if ($forceExport) {
            $res = $this->getExporter($scope)->export();
            if (is_array($res)) {
                echo json_encode($res);
            } else {
                echo $res;
            }
            exit();
        }
    }



    public function render()
    {
        $result = $this->handleExportRequest(true);

        try {
            $this->build();
        } catch (\Exception $e) {
            return Handler::renderException($e);
        }

        return view($this->view, $this->variables())->render();
    }

    /**
     * @return string
     */
    public function getWenExporter()
    {
        return $this->exporter;
    }



    /**
     * Setup grid filter.
     *
     * @return void
     */
    protected function setupFilter()
    {
        //dump('setupFilter---');
        $this->filter = new WenFilter($this->model());
    }


    /**
     * @param string $scope
     *
     * @return AbstractExporter
     */
//    protected function getExporter($scope)
//    {
//        //dump('getExporter---');
//        (new Grid\Exporter($this))->resolve($this->exporter)->withScope($scope);
//    }


}