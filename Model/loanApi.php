<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    function insertNew($t, $txt, $money, $name = "") {
        global $dblink;
        if (!preg_match("/^\d{10}$/", $t)) {
            return false;
        }
        if ($money == "" || $money == 0) {
            return false;
        }
        $reQuery = $dblink->prepare("CALL `insertNewPayPal`(:p2, :p3, :p4, :today, :p5, @p6, @p7, @p8, @p9, @p10, @p11, @p12, @p13, @p14);");
        $reQuery->execute(array(":p2" => $txt, ":p3" => $money, ":p4" => $t, ":p5" => $name, ":today" => strtotime(date("Y-m-d"))));
        $re = $dblink->query("SELECT @p6 AS `loanID1`, @p7 AS `loanID2`, @p8 AS `transactionsID1`, @p9 AS `transactionsID2`, @p10 AS `lowOut`, @p11 AS `highOut`, @p12 AS `closedOut`, @p13 AS `incomeOut`, @p14 AS `expendOut`;")->fetch(PDO::FETCH_ASSOC);
        if ($re["loanID1"] == 0 && $re["loanID2"] == 0 && $re["transactionsID1"] == 0 && $re["transactionsID2"] == 0) {
            return false;
        }
        $re["txt"] = $txt;
        $re["money"] = $money;
        $re["name"] = $name;
        return $re;
    }