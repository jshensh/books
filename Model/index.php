<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    function getTransactModeList() {
        return $GLOBALS["dblink"]->query("select * from `transactMode` order by `sortId`,`id`")->fetchAll(PDO::FETCH_ASSOC);
    }

    function insertNew($mode,$txt,$money,$name="") {
        global $dblink;
        list($outTransactMode,$inTransactMode)=explode("_", $mode);
        if (!($outTransactMode>=0 && is_numeric($outTransactMode) && $inTransactMode>=0 && is_numeric($inTransactMode) && $outTransactMode+$inTransactMode>0)) {
            return false;
        }
        if ($money=="" || $money==0) {
            return false;
        }
        if ($outTransactMode>0 && $inTransactMode>0) {
            $name="";
        } else {
            if ($outTransactMode>0) {
                $money=-abs($money);
            } else {
                $money=abs($money);
            }
        }
        $reQuery=$dblink->prepare("CALL `insertNew`(:p0, :p1, :p2, :p3, :p4, :today, :p5, @p6, @p7, @p8, @p9, @p10, @p11, @p12, @p13, @p14);");
        $reQuery->execute(array(":p0"=>$outTransactMode,":p1"=>$inTransactMode,":p2"=>$txt,":p3"=>$money,":p4"=>time(),":p5"=>$name,":today"=>strtotime(date("Y-m-d"))));
        $re=$dblink->query("SELECT @p6 AS `loanID1`, @p7 AS `loanID2`, @p8 AS `transactionsID1`, @p9 AS `transactionsID2`, @p10 AS `lowOut`, @p11 AS `highOut`, @p12 AS `closedOut`, @p13 AS `incomeOut`, @p14 AS `expendOut`;")->fetch(PDO::FETCH_ASSOC);
        if ($re["loanID1"]==0 && $re["loanID2"]==0 && $re["transactionsID1"]==0 && $re["transactionsID2"]==0 && $re["lowOut"]==0 && $re["highOut"]==0 && $re["closedOut"]==0 && $re["incomeOut"]==0 && $re["expendOut"]==0) {
            return false;
        }
        $re["transactMode"]=$mode;
        $re["txt"]=$txt;
        $re["money"]=$money;
        $re["name"]=$name;
        return $re;
    }

    function doRollback() {
        global $dblink;
        $rollbackID=json_decode($_SESSION["rollbackID"],1);
        if ($rollbackID["loanID1"]===null || $rollbackID["loanID2"]===null || $rollbackID["transactionsID1"]===null || $rollbackID["transactionsID2"]===null || $rollbackID["lowOut"]===null || $rollbackID["highOut"]===null || $rollbackID["closedOut"]===null || $rollbackID["incomeOut"]===null || $rollbackID["expendOut"]===null) {
            return false;
        }
        $reQuery=$dblink->prepare("CALL `doRollback`(:p0, :p1, :p2, :p3, :p4, :p5, :p6, :p9, :p10, :p7, @p8);");
        $reQuery->execute(array(":p0"=>$rollbackID["loanID1"],":p1"=>$rollbackID["loanID2"],":p2"=>$rollbackID["transactionsID1"],":p3"=>$rollbackID["transactionsID2"],":p4"=>$rollbackID["lowOut"],":p5"=>$rollbackID["highOut"],":p6"=>$rollbackID["closedOut"],":p9"=>$rollbackID["incomeOut"],":p10"=>$rollbackID["expendOut"],":p7"=>strtotime(date("Y-m-d"))));
        //var_dump("CALL `doRollback`('{$rollbackID["loanID1"]}', '{$rollbackID["loanID2"]}', '{$rollbackID["transactionsID1"]}', '{$rollbackID["transactionsID2"]}', '{$rollbackID["lowOut"]}', '{$rollbackID["highOut"]}', '{$rollbackID["closedOut"]}', '".strtotime(date("Y-m-d"))."', '{$rollbackID["incomeOut"]}', '{$rollbackID["expendOut"]}', @p8);");
        $re=$dblink->query("SELECT @p8 AS `status`;")->fetch(PDO::FETCH_ASSOC);
        unset($_SESSION["rollbackID"]);
        return $re["status"]==0?false:array("transactMode"=>$rollbackID["transactMode"],"txt"=>$rollbackID["txt"],"money"=>$rollbackID["money"],"name"=>$rollbackID["name"]);
    }

    $transactModeList=json_stringify(getTransactModeList());
    $op=-1;
    if ($_POST) {
        if ($_SESSION["token"]===$_POST["token"]) {
            $rollbackSess=insertNew($_POST["transactMode"],$_POST["txt"],$_POST["money"],$_POST["name"]);
            if (!$rollbackSess) {
                $op=0;
            } else {
                $_SESSION["rollbackID"]=json_stringify($rollbackSess);
                $op=1;
            }
        }
    } else {
        if ($_GET["rollback"]=="true" && !$_POST) {
            $rollbackStatus=doRollback();
        }
    }
    $token=time().getRandChar(3);
    $_SESSION["token"]=$token;

    $amount=json_stringify($dblink->query("select `a`.`id`,`a`.`name`, ifnull(sum(`b`.`money`),0) as `s` from `transactMode` as `a` left join `transactions` as `b` on `b`.`transactMode` = `a`.`id` group by `a`.`id` order by `a`.`sortId`, `a`.`id`")->fetchAll(PDO::FETCH_ASSOC));
    $loanSum=json_stringify($dblink->query("select sum(`money`) as `s` from `loan`")->fetch(PDO::FETCH_ASSOC));
    $yesterdayTotal=$dblink->query("select if(count(`id`)=0,0,`closed`) as `closed` from (select `id`,`closed` from `statements` where `t` != ".strtotime(date("Y-m-d"))." order by `t` desc limit 1 offset 0)tb")->fetch(PDO::FETCH_ASSOC);
    $todayTotal=$dblink->query("select if(count(`id`)=0,0,`closed`) as `closed` from `statements` where `t` = ".strtotime(date("Y-m-d")))->fetch(PDO::FETCH_ASSOC);
    $todayTotal["closed"]=$todayTotal["closed"]==0?0:($todayTotal["closed"]-$yesterdayTotal["closed"]); 
    $todayTotal=json_stringify($todayTotal);