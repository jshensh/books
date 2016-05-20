<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    $setName=0;
    $name=ucAuthCode($_GET["token"],"DECODE",$key);
    $data=json_stringify(0);
    if ($name) {
        $op=$dblink->prepare("select `loan`.`id`,`loan`.`name`,`transactMode`.`name`,`loan`.`money`,`loan`.`txt`,`loan`.`t` from `loan`,`transactMode` where `loan`.`name`=:name and `transactMode`.`id`=`loan`.`transactMode` order by `loan`.`t`;");
        $op->execute(array(":name"=>$name));
        $dataTmp=$op->fetchAll(PDO::FETCH_ASSOC);
        if (count($dataTmp)!==0) {
            $data=json_stringify($dataTmp);
            $setName=1;
        }
    }