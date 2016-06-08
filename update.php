<?php
    require("config.php");

    $data=$dblink->query("SELECT * FROM `transactions` where `txt` not like '%借款%' and `txt` not like '%还款%' and `txt` not like '%销账%' and `txt` not like '%充值%' and `txt` not like '%提现%' order by `id`")->fetchAll();

    $tData=array();

    foreach ($data as $value) {
        //var_dump($value["t"]);
        $tData[strtotime(date("Y-m-d",$value["t"]))][$value["money"]>0?"income":"expend"]+=abs($value["money"]);
    }
    
    $updateQ=$dblink->prepare("update `statements` set `income`=:income, `expend`=:expend where `t`=:t");
    foreach ($tData as $key => $value) {
        $updateQ->execute(array("income"=>$value["income"],"expend"=>$value["expend"],"t"=>$key));
    }