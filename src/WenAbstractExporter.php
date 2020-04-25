<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 19:22
 */

namespace Wenruns\Grid;


use Encore\Admin\Grid;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Encore\Admin\Layout\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Facades\Excel;

class WenAbstractExporter extends AbstractExporter
{
    protected $head = []; // excel头信息

    protected $body = []; // excel导出字段

    protected $fileName = null; // excel文件名


    protected $fileType = 'xlsx'; // 导出excel文件格式

    protected $sheet_lines = 0;  // 每个sheet的条数

    protected $sheet_wpx = []; // 单元格宽度


    protected $footer_content = '';  // 脚部文本

    protected $header_content = '';  // 头部文本

    protected $custom_export_processor = false; // 启用自定义导出处理程序


    /**
     * 用户自定义导出处理
     * @return bool
     */
    public function exportProcessor()
    {
        // Todo:自定义导出处理功能
    }


    /**
     * 设置excel单元格宽度
     * @param $field
     * @param int $w
     */
    protected function width($field, $w = -1)
    {
        $wt = [
            'wpx' => $w,
        ];
        $key = array_search($field, $this->body);
        if (isset($this->sheet_wpx[$key])) {
            $this->sheet_wpx[$key] = $wt;
        } else {
            $this->sheet_wpx[] = $wt;
        }
        return $this;
    }


    /**
     * 获取excel单元格宽度设置
     * @return array
     */
    public function getWidth()
    {
        return $this->sheet_wpx;
    }


    /**
     * @return int
     * 设置每次查询条数
     */
    public function setPerPage()
    {
        return 1000;
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
        return $this;
    }


    /**
     * 设置每个sheet的记录条数
     * @param int $lines
     * @return $this
     */
    public function sheetLines($lines = 5000)
    {
        $this->sheet_lines = $lines;
        return $this;
    }

    /**
     * 获取每个sheet的记录条数
     * @return int
     */
    public function getSheetLines()
    {
        return $this->sheet_lines;
    }


    /**
     * 允许excel表头输出字符串，可以返回一个数组或字符串
     * @param array $data
     * @return string
     */
    public function setHeader($data = [])
    {
        return '';
    }

    /**
     * 允许在excel末尾输出字符串，可以返回一个数组或者字符串
     * @param array $data
     * @return string
     */
    public function setFooter($data = [])
    {
        return '';
    }


    /**
     * @return array|mixed
     * 返回查询结果
     */
    public function export()
    {
        if ($this->custom_export_processor) {
            return $this->response($this->exportProcessor(), '用户自定义回调处理导出功能');
        }
        return $this->response($this->makeData());
    }

    /**
     * @return mixed
     * 获取数组
     */
    protected function makeData()
    {
        $data = $this->getData(true);
        $cache_data = Session::pull('wen_export_data_cache', []);
        if (count($data) < $this->setPerPage()) {
            $cache_data = array_merge($cache_data, $data);
            $this->footer_content = $this->setFooter($cache_data);
            $this->header_content = $this->setHeader($cache_data);
        } else {
            $cache_data = array_merge($cache_data, $data);
            Session::put('wen_export_data_cache', $cache_data);
        }
        Session::save();
        return $this->format($data);
    }

    /**
     * @param $data
     * @return mixed
     * 预留数据处理回调
     */
    public function format($data)
    {
        $res = array_map(function ($item) {
            $arr = [];
            foreach ($this->body as $field) {
                $arr[] = array_get($item, $field);
            }
            return $arr;
        }, $data);
        return $res;
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
     * 分页查询处理
     * @return array
     */
    protected function getExportOptions()
    {
        $this->grid->model()->usePaginate(false);
        $limitNum = $this->setPerPage(); // 每次查询最大限制，防止服务器内存溢出问题
        $scope = request('_export_'); // 导出标志（全部：all，当前页：page:n，选择行：selected:ids，指定范围页：page:）
        $nowPage = request('pageN');

//        dd(request()->toArray(), $this->grid->getPerPageName());
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
            ], [
                'offset' => [$offset]
            ]
        ];
    }

    /**
     * 设置excel导出文件的字体
     * @return string
     */
    public function setFontFamily()
    {
        return "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif";
    }

    /**
     * 返回导出数据
     * @param $data
     * @param string $msg
     * @return array
     */
    protected function response($data, $msg = '')
    {
        if ($this->custom_export_processor) {
            return [
                'finished' => true,
                'status' => true,
                'code' => 200,
                'msg' => $msg,
                'data' => $data,
            ];
        }
        return [
            'finished' => count($data) < $this->setPerPage() ? true : false,
            'status' => true,
            'code' => 2000,
            'msg' => $msg,
            'data' => $data,
            'width' => $this->getWidth(),
            'header' => $this->header_content,
            'footer' => $this->footer_content,
        ];
    }

    /**
     * 获取头信息
     * @return array
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * 获取body字段信息
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 获取文件名称
     * @return |null
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * 获取导出文件格式
     * @return string
     */
    public function getType()
    {
        return $this->fileType;
    }

    /**
     * @return array
     * 设置导入文件后缀名
     */
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

    /**
     * @param array $data
     * 导入数据处理
     */
    public function import(array $data)
    {
    }


    /**
     * Export data with scope.
     *
     * @param string $scope
     *
     * @return $this
     */
    public function withScope($scope)
    {
        if ($scope == Grid\Exporter::SCOPE_ALL) {
            return $this;
        }

        list($scope, $args) = explode(':', $scope);

        if ($scope == Grid\Exporter::SCOPE_CURRENT_PAGE) {
            $this->grid->model()->usePaginate(true);
            $this->page = $args ?: 1;
        }

        if ($scope == Grid\Exporter::SCOPE_SELECTED_ROWS) {
            $selected = explode(',', $args);
            $this->grid->model()->whereIn($this->grid->model()->getTable() . '.' . $this->grid->getKeyName(), $selected);
        }

        return $this;
    }

    public function getGrid()
    {
        return $this->grid;
    }
}