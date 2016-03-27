<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    $setName=0;
    $getList=false;
    if ($_POST["delete"]=="true") {
        $insertQry=$dblink->prepare("insert into `transactions`(`transactMode`,`money`,`txt`,`t`) select 1,-`s`,concat(`name`,\"销账\"),:t from (SELECT sum(`money`) as `s`,`name` FROM `loan` group by `name`)t1 WHERE s!=0 and `name`=:name;");
        $insertQry->execute(array(":name"=>$_GET["name"],":t"=>strtotime(date("Y-m-d"))));
        $delQry=$dblink->prepare("delete from `loan` where `name`=:name;");
        $delQry->execute(array(":name"=>$_GET["name"]));
        $getList=true;
    } else {
        if ($_GET["name"]) {
            $op=$dblink->prepare("select `loan`.`id`,`loan`.`name`,`transactMode`.`name`,`loan`.`money`,`loan`.`txt`,`loan`.`t` from `loan`,`transactMode` where `loan`.`name`=:name and `transactMode`.`id`=`loan`.`transactMode` order by `loan`.`t`;");
            $op->execute(array(":name"=>$_GET["name"]));
            $dataTmp=$op->fetchAll(PDO::FETCH_ASSOC);
            if (count($dataTmp)===0) {
                $getList=true;
            } else {
                $data=json_stringify($dataTmp);
                $setName=1;
            }
        } else {
            $getList=true;
        }
    }
    if ($getList) {
        $data=json_stringify($dblink->query("select `name`,sum(`money`) as `all`,min(`t`) as `minT`,max(`t`) as `maxT` from `loan` group by `name` order by `all`;")->fetchAll(PDO::FETCH_ASSOC));
    }