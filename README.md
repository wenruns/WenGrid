<h2>laravel-admin导出大量数据内存不足的解决方案</h2>

<h3>1、目的和原理</h3>
&emsp;目的：解决laravel-admin自带的导出出现内存不足的问题。也相当于是laravel-admin自带导出功能的替代品。<br/>

&emsp;原理：laravel-admin自带的导出功能，数据格式化都是在服务器上循环执行，通过Excel类生成excel表格然后返回执行导出。而本插件是是将excel生成模块剥离出来，在浏览器通过js来实现。而服务知识纯粹的获取数据，或者做简单的数据格式。并且通过ajax轮询查询数据，可控制每次查询的记录条数，以此达到防止内存溢出的目的。
    
<h3>2、使用教程</h3>
（1）Grid类的替换<br/>
&emsp;&emsp;本功能继承Grid重写了某些方法，达到替换的目的，在使用本插件的时候，使用WenGrid类替换Grid类，并不会影响Grid类中的原本功能。<br/>
（2）AbStractExporter类的替换<br/>
&emsp;&emsp;原本laravel-admin中的导出功能需要新建一个导出类继承于AbstractExporter类，用来实现export方法.<br/>
&emsp;&emsp;而本插件则继承AbstarctExporter类重写某些方法，使用WenAbstractExporter类替换AbstractExporter类，并且不需要做太多的事情，只需实现一个格式化的方法函数setFormat，需要注意的是，该函数的实现体是要返回一个javascript匿名函数，并且该匿名函数将获的两个参数，详情参考下文。<br/>
（3）示例
 <h5>导出类TestExporter</h5>
class TestExporter extends WenAbstractExporter{<br/>
&emsp;/\*\*<br/>
&emsp;* @return int<br/>
&emsp;* 设置每次查询条数<br/>
&emsp;* （默认为500）<br/>
&emsp;*/<br/>
&emsp;public function setPerPage()<br/>
&emsp;{<br/>
&emsp;&emsp;return 500;
<br/>&emsp;}<br/>

&emsp;/\*\*<br/>
&emsp;* @return string 或 array<br/>
&emsp;* 允许在excel末尾输出字符串，可以返回一个数组或者字符串<br/>
&emsp;* （默认为空，如果需要在excel表格后面输出提示或者其他信息，可在此输出（可换行））<br/>
&emsp;*/<br/>
&emsp;public function setFooter()<br/>
&emsp;{<br/>
&emsp;&emsp;return '';<br/>
&emsp;}<br/>

&emsp;/\*\*<br/>
&emsp;* @return string<br/>
&emsp;* 允许excel表头输出字符串，可以返回一个数组或字符串<br/>
&emsp;* （默认为空，如果需要在excel表头输出提示或者其他信息，可在此输出（可换行））<br/>
&emsp;*/<br/>
&emsp;public function setHeader()<br/>
&emsp;{<br/>
&emsp;&emsp;return '';<br/>
&emsp;}<br/>

 &emsp;/\*\*<br/>
&emsp;* @return string<br/>
&emsp;* （此为默认方法）设置格式化方法，返回一个JavaScript匿名方法，参数一个数据集合和body字段<br/>
&emsp;*/<br/>
&emsp;public function setFormat() {<br/>
&emsp;&emsp;return <<<SCRIPT<br/>
&emsp;&emsp;function(item, field){<br/>
&emsp;&emsp;&emsp;index = field.split('.');<br/>
&emsp;&emsp;&emsp;index.forEach(function(field, dex){<br/>
&emsp;&emsp;&emsp;&emsp;if (!item || !item[field]) {<br/>
&emsp;&emsp;&emsp;&emsp;&emsp;item = '';<br/>
&emsp;&emsp;&emsp;&emsp;&emsp;return;<br/>
&emsp;&emsp;&emsp;&emsp;}<br/>
&emsp;&emsp;&emsp;&emsp;item = item[field];<br/>
&emsp;&emsp;&emsp;});<br/>
&emsp;&emsp;&emsp;return item;<br/>
&emsp;&emsp;}<br/>
&emsp;&emsp;SCRIPT;<br/>
&emsp;}<br/>

&emsp;/\*\*<br/>
&emsp;* @return string<br/>
&emsp;* 设置excel导出文件的字体<br/>
&emsp;* (默认为 "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif")<br/>
&emsp;*/<br/>
&emsp;public function setFontFamily()<br/>
&emsp;{<br/>
&emsp;&emsp;return "'Source Sans Pro','Helvetica Neue',Helvetica,Arial,sans-serif";<br/>
&emsp;}<br/>

&emsp;/\*\*<br/>
&emsp;* @return array<br/>
&emsp;* 设置导入文件后缀名<br/>
&emsp;* （默认为xlsx和xls）<br/>
&emsp;*/<br/>
&emsp;public function setImportTypes()<br/>
&emsp;{<br/>
&emsp;&emsp;return ['xlsx', 'xls'];<br/>
&emsp;}<br/>

&emsp;/\*\*<br/>
&emsp;* @param array $data<br/>
&emsp;* 导入数据库处理<br/>
&emsp;* （如果启用导入功能，可在此方法实现数据格式化并且导入数据库的功能）<br/>
&emsp;*/<br/>
&emsp;public function import(array $data)<br/>
&emsp;{<br/>
&emsp;&emsp; var_dump($data);<br/>
&emsp;&emsp; // todo:: 导入数据库处理<br/>
&emsp;}<br/>
<br/>}

<hr/>
 <h5>控制器TestController</h5>
class TestController{<br/>
&emsp;function test(){<br/>
&emsp;&emsp;$grid = new WenGrid(new Model());<br/>
&emsp;&emsp;$excel = new TestExporter();<br/>
&emsp;&emsp;$head = ['申请书编号', '产品', '营销人员', '客户姓名']; // excel表头<br/>
&emsp;&emsp;$body = ['field_1', 'field_2', 'field_3', 'field_4']; // 导出字段<br/>
&emsp;&emsp;$fileName = 'Excel文件名称'; // 导出excel表名称<br/>
&emsp;&emsp;$excel->setAttr($head, $body, $fielName);<br/>
&emsp;&emsp;$grid->exporter($excel);<br/>
&emsp;&emsp;$grid->showImporter(); // 显示导入按钮（功能）<br/>
&emsp;&emsp;..........
&emsp;&emsp;
<br/>&emsp;}
<br/>}
<hr/>


        
        
    
    