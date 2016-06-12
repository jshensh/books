<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    $setName=0;
    $getList=false;
    if ($_POST["delete"]=="true") {
        $insertQry=$dblink->prepare("insert into `transactions`(`transactMode`,`money`,`txt`,`t`) select 1,-`s`,concat(`name`,\"销账\"),:t from (SELECT sum(`money`) as `s`,`name` FROM `loan` group by `name`)t1 WHERE s!=0 and `name`=:name;");
        $insertQry->execute(array(":name"=>$_GET["name"],":t"=>time()));
        $delQry=$dblink->prepare("delete from `loan` where `name`=:name;");
        $delQry->execute(array(":name"=>$_GET["name"]));
        $getList=true;
    } elseif ($_POST["shareTime"]) {
        if (is_numeric($_POST["shareTime"]) && $_POST["shareTime"]>0) {
            $token=urlencode(ucAuthCode($_GET["name"],"ENCODE",$key,$_POST["shareTime"]*60));
            $shortLink=json_decode(curl_get_contents("http://api.t.sina.com.cn/short_url/shorten.json?source=3213676317&url_long=".urlencode("{$_POST['path']}loanShare?token={$token}")),1);
            if (!$shortLink) {
                echo json_stringify(array("status"=>"error"));
            } else {
                echo json_stringify(array("status"=>"success","link"=>$shortLink[0]["url_short"]));
            }
        } else {
            echo json_stringify(array("status"=>"error"));
        }
        exit();
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