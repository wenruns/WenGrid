<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 19:22
 */

namespace vendor\WenGrid;


use Encore\Admin\Grid;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Encore\Admin\Layout\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Facades\Excel;

abstract class WenAbstractExporter extends AbstractExporter
{
    protected $head = []; // excel头信息

    protected $body = []; // excel导出字段

    protected $fileName = null; // excel文件名


    protected $fileType = 'xlsx'; // 导出excel文件格式


    /**
     * @return int
     * 设置每次查询条数
     */
    public function setPerPage()
    {
        return 500;
    }

    /**
     * @param $head
     * @param $body
     * @param $fileName
     * @param string $type
     * 设置导出excel属性
     */
    public function setAttr($head, $body, $fileName, $type = 'xlsx')
    {
        $this->head = $head;
        $this->body = $body;
        $this->fileName = $fileName;
        $this->fileType = $type;
    }


    /**
     * @return string 或 array
     * 允许在excel末尾输出字符串，可以返回一个数组或者字符串
     */
    public function setFooter()
    {
        return '';
    }

    /**
     * @return string
     * 允许excel表头输出字符串，可以返回一个数组或字符串
     */
    public function setHeader()
    {
        return '';
    }

    /**
     * @return string
     * 设置格式化方法，返回一个JavaScript匿名方法，参数一个数据集合和body字段
     */
    public function setFormat() {
        return <<<SCRIPT
    function(item, field){
        index = field.split('.');
        index.forEach(function(field, dex){
            if (!item || !item[field]) {
                item = '';
                return;
            }
            item = item[field];
        });
        
        return item;
    }
SCRIPT;
    }

    /**
     * @return array|mixed
     * 返回查询结果
     */
    public function export()
    {
        return $this->response($this->makeData());
    }

    /**
     * @return mixed
     * 获取数组
     */
    protected function makeData()
    {
        $data = $this->format($this->getData(true));

        return $data;
    }

    /**
     * @param $data
     * @return mixed
     * 预留数据处理回调
     */
    public function format($data)
    {
        return $data;
    }

    /**
     * Get data with export query.
     *
     * @param bool $toArray
     *
     * @return array|\Illuminate\Support\Collection|mixed
     */
    public function getData($toArray = true)
    {
        return $this->grid->getFilter()->execute($toArray, $this->getExportOptions());
    }


    /**
     * @return array
     * 分页查询处理
     */
    protected function getExportOptions()
    {
        $this->grid->model()->usePaginate(false);
        $limitNum = $this->setPerPage(); // 每次查询最大限制，防止服务器内存溢出问题
        $scope = request('_export_'); // 导出标志（全部：all，当前页：page:n，选择行：selected:ids，指定范围页：page:）
        $nowPage = request('pageN');
        if (strpos($scope, 'page:') !== false) {
            $perPage = request('per_page'); // 当前每页显示的条数
            if ($range = request('pageRange')) { // 导出指定页数
                $pages = $range['end'] - $range['start'] + 1; // 共导出n页
                $offset = ($range['start'] - 1) * $perPage; // 起始索引值
            } else { // 导出当前页
                $pages = 1; // 共导出1页
                $range = explode(':', $scope); // 获取需要导出的页数
                $offset = ($range[1] - 1) * $perPage; // 起始索引值
            }
            $totalNum = $pages * $perPage; // 一共需要导出记录条数
            if ($limitNum > $totalNum) {
                $limitNum = $totalNum;
            }
            $offset += $nowPage * $limitNum; // 导出第几页
        } else if (strpos($scope, 'selected:') !== false) { // 导出选择行
            $offset = $nowPage * $limitNum;
        } else { // 导出全部
            $offset = $nowPage * $limitNum;
        }
        return [
            [
                'limit' => [$limitNum]
            ],[
                'offset' => [$offset]
            ]
        ];
    }

    public function setFontFamily()
    {
        return "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif";
    }

    /**
     * @param $data
     * @param string $msg
     * @return array
     */
    protected function response($data, $msg = '')
    {
        return [
            'finished' => count($data) < $this->setPerPage() ? true : false,
            'status' => true,
            'code' => 2000,
            'msg' => $msg,
            'data' => $data,
        ];
    }
    public function getHead()
    {
        return $this->head;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getType()
    {
        return $this->fileType;
    }

    public function setImportTypes()
    {
        return ['xlsx', 'xls'];
    }

    public function importRun(Grid $grid)
    {
        $file = Input::file('import');
        $data = [];
        if ($file) {
            $data = Excel::load($file->getRealPath())->all()->toArray();
        }
        $response = $this->import($data);

        if ($response instanceof RedirectResponse) {
            echo $response->sendHeaders();
        } else {
            echo redirect($grid->resource())->sendHeaders();
        }
        exit(0);
    }

    public function import(array $data){}
}