<?php
    error_reporting(E_ALL & ~E_NOTICE);
    define('ROOT_DIR',dirname(__FILE__));

    $dbhost = ""; //服务器
    $dbuser = ""; //用户名
    $dbpass = ""; //密码
    $dbname = ""; //库名
    $port = "3306"; //端口

    $loginPassword = ""; //登录密码
    $key = ""; //加密 key，一定要修改，任意字符均可
    $expiry = 604800; //cookie 有效期（单位：秒）

    try {
        $dblink = new PDO("mysql:host={$dbhost};port={$port};dbname={$dbname}", $dbuser, $dbpass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $dblink->exec('set names utf8');
    } catch (PDOException $e) {
        echo "Error!: " . $e->getMessage();
        die();
    }
    session_start();
    date_default_timezone_set('Asia/Shanghai');