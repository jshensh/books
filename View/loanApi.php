<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    if ($_POST) {
        if ($_POST["sign"]!==md5("s{$_POST['name']}i{$_POST['txt']}g{$_POST['money']}n")) {
            echo json_stringify(["errno"=>-1]);
            exit();
        }
        if (insertNew(time(),$_POST['txt'],$_POST['money'],$_POST['name'])) {
            echo json_stringify(["errno"=>0,[$_POST['name'],$_POST['txt'],$_POST['money']]]);
        } else {
            echo json_stringify(["errno"=>-2]);
        }
    }