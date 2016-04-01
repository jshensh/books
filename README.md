账簿
====

首先说明，作者不是做财务的，部分概念可能会有错误，请各位指出，谢谢。
因为是小项目，程序没有使用任何 PHP 框架，而前端唯一使用到的只有 highstock 以及 highstock 依赖的 jQuery。
手机上用得比较多，懒得写 UI，各位好心人可以帮忙加上。。。

作者博客 [http://233.imjs.work/](http://233.imjs.work/)

###安装
导入 import.sql，修改 config.php

    $dbhost=""; //服务器
    $dbuser=""; //用户名
    $dbpass=""; //密码
    $dbname=""; //库名
    $port="3306"; //端口

    $loginPassword=""; //登录密码
    $key=""; //加密 key，一定要修改，任意字符均可
    $expiry=604800; //cookie 有效期（单位：秒）

添加 rewrite 规则（Apache 请自行百度）

    location ~ ^/[^\/\.]*$ {
        if (!-e $request_filename) {
            rewrite ^ /index.php last;
        }
    }

###添加交易方式
需要在数据库手动添加
![screenshot2](http://233.imjs.work/wp-content/uploads/2016/03/QQ截图20160327220407.jpg)

###截图
![screenshot1](http://233.imjs.work/wp-content/uploads/2016/03/QQ截图20160327202619.jpg)