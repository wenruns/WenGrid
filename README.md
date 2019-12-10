laravel-admin导出大量数据内存不足的解决方案
====================

1、目的和原理
--------------
```
目的：解决laravel-admin自带的导出出现内存不足的问题。也相当于是laravel-admin自带导出功能的替代品。

原理：laravel-admin自带的导出功能，数据格式化都是在服务器上循环执行，通过Excel类生成excel表格然后返回执行导出。而本插件是是将excel生成模块剥离出来，在浏览器通过js来实现。而服务知识纯粹的获取数据，或者做简单的数据格式。并且通过ajax轮询查询数据，可控制每次查询的记录条数，以此达到防止内存溢出的目的。
```

2、安装
--------
（1）安装环境
```
laravel版本号：5.5.44
laravel-admin版本号：1.6.10
php版本号：7+
maatwebsite/excel版本号：~2.1.0
```
（2）安装
```php
composer require wenruns/grid
```

3、使用教程
------------
（1）Grid类的替换

```
本功能继承Grid重写了某些方法，达到替换的目的，在使用本插件的时候，使用WenGrid类替换Grid类，并不会影响Grid类中的原本功能。
```

（2）AbStractExporter类的替换

```
原本laravel-admin中的导出功能需要新建一个导出类继承于AbstractExporter类，用来实现export方法。

而本插件则继承AbstarctExporter类重写某些方法，使用WenAbstractExporter类替换AbstractExporter类，并且不需要做太多的事情，只需实现一个格式化的方法函数setFormat，需要注意的是，该函数的实现体是要返回一个javascript匿名函数，并且该匿名函数将获的两个参数，详情参考下文。
```

（3）示例

- 导出类TestExporter
 
```php
class TestExporter extends WenAbstractExporter{
    /**
    * @return int
    * 设置每次查询条数
    * （默认为500）
    */
    public function setPerPage()
    {
        return 500;
    }

    /**
    * @return string 或 array
    * 允许在excel末尾输出字符串，可以返回一个数组或者字符串
    * （默认为空，如果需要在excel表格后面输出提示或者其他信息，可在此输出（可换行））
    */
    public function setFooter()
    {
        return '';
    }

    /**
    * @return string
    * 允许excel表头输出字符串，可以返回一个数组或字符串
    * （默认为空，如果需要在excel表头输出提示或者其他信息，可在此输出（可换行））
    */
    public function setHeader()
    {
        return '';
    }

    /**
    * @return string
    * （此为默认方法）设置格式化方法，返回一个JavaScript匿名方法，参数一个数据集合和body字段
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
    * @return string
    * 设置excel导出文件的字体
    * (默认为 "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif")
    */
    public function setFontFamily()
    {
        return "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif";
    }

    /**
    * @return array
    * 设置导入文件后缀名
    * （默认为xlsx和xls）
    */
    public function setImportTypes()
    {
        return ['xlsx', 'xls'];
    }

    /**
    * @param array $data
    * 导入数据库处理
    * （如果启用导入功能，可在此方法实现数据格式化并且导入数据库的功能）
    */
    public function import(array $data)
    {
        var_dump($data);
        // todo:: 导入数据库处理
    }
}
```

- 控制器TestController
 
```php
class TestController{
    function test(){
        $grid = new WenGrid(new Model());
        $excel = new TestExporter();
        $head = ['申请书编号', '产品', '营销人员', '客户姓名']; // excel表头
        $body = ['field_1', 'field_2', 'field_3', 'field_4']; // 导出字段
        $fileName = 'Excel文件名称'; // 导出excel表名称
        $excel->setAttr($head, $body, $fielName);
        $grid->exporter($excel);
        $grid->showImporter(); // 显示导入按钮（功能）
        ......
    }
}
```
        
        
    
    