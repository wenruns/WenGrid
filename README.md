#laravel-admin导出大量数据内存不足的解决方案

##1、目的和原理
    目的：解决laravel-admin自带的导出出现内存不足的问题。也相当于是laravel-admin自带导出功能的替代品。
    
    原理：laravel-admin自带的导出功能，数据格式化都是在服务器上循环执行，通过Excel类生成excel表格然后返回执行导出。而本插件是是将excel生成模块剥离出来，在浏览器通过js来实现。而服务知识纯粹的获取数据，或者做简单的数据格式。并且通过ajax轮询查询数据，可控制每次查询的记录条数，以此达到防止内存溢出的目的。
    
##2、使用教程
    （1）Grid类的替换
        本功能继承Grid重写了某些方法，达到替换的目的，在使用本插件的时候，使用WenGrid类替换Grid类，并不会影响Grid类中的原本功能。
    （2）AbStractExporter类的替换
        原本laravel-admin中的导出功能需要新建一个导出类继承于AbstractExporter类，用来实现export方法；
        而本插件则继承AbstarctExporter类重写某些方法，使用WenAbstractExporter类替换AbstractExporter类，并且不需要做太多的事情，只需实现一个格式化的方法函数setFormat，需要注意的是，该函数的实现体是要返回一个javascript匿名函数，并且该匿名函数将获的两个参数，详情参考下文。
    （3）具体实现和laravel-admin自带的功能实现差不多。
        
##3、类的介绍

        
        
    
    