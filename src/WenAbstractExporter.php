<?php
/**
 * Created by PhpStorm.
 * User: wen
 * Date: 2019/10/26
 * Time: 19:22
 */

namespace wenvender\wengrid;


use Encore\Admin\Grid\Exporters\AbstractExporter;

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
        return 150;
    }

    public function setAttr($head, $body, $fileName, $type = 'xlsx')
    {
        $this->head = $head;
        $this->body = $body;
        $this->fileName = $fileName;
        $this->fileType = $type;
    }


    public function setFooter()
    {
        return '';
    }


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

    public function export()
    {
        return $this->response($this->makeData());
    }

    protected function makeData()
    {
        $data = $this->format($this->getData(true));

        return $data;
    }

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

//    protected $message = '';

    protected function getExportOptions()
    {
        $this->grid->model()->usePaginate(false);
//        dump(request()->toArray());
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
//            dump($pages, $perPage, $limitNum, $offset, $totalNum, $nowPage);
//            $this->message = 'pages:'.$pages.'-perpage:'.$perPage.'-limitNum:'.$limitNum.'-offset:'.$offset.'-totalNum:'.$totalNum.'-nowPage'.$nowPage;
        } else if (strpos($scope, 'selected:') !== false) {
            $offset = $nowPage * $limitNum;
        } else {
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
}