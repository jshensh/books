<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }
    $t=(is_numeric($_GET["t"]) && $_GET["t"]>0)?$_GET["t"]:strtotime(date("Y-m-d"));
    $mode=(is_numeric($_GET["transactMode"]) && $_GET["transactMode"]>0)?$_GET["transactMode"]:"";
    $transactMode=json_stringify($dblink->query("select `id`,`name` from `transactMode` order by `id`")->fetchAll(PDO::FETCH_ASSOC));
    $transactions=json_stringify($dblink->query("select * from `transactions` where `t`<{$t}+86400 and `t`>='{$t}' ".($mode?"and `transactMode`='{$mode}' ":"")."order by `t` desc, `id` desc")->fetchAll(PDO::FETCH_ASSOC));