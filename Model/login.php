<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    if ($_POST["password"]) {
        $token = checkLogin($_POST["password"]);
        if ($token) {
            setcookie("token", $token, time() + $expiry);
            header("Location: index");
            exit();
        }
    }