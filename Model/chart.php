<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    $statements=json_stringify($dblink->query("select * from `statements` order by `id`")->fetchAll(PDO::FETCH_ASSOC));