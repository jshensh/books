<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }
    
    $len = strpos($_SERVER["REQUEST_URI"], "?");
    $command = $len ? substr($_SERVER["REQUEST_URI"], 1, $len-1) : substr($_SERVER["REQUEST_URI"], 1);
    $command = $command ? $command : "index";

    switch ($command) {
        case 'loanShare':
        case 'loanApi':
            require("Model/{$command}.php");
            require("View/{$command}.php");
            exit();
    }
    if (!checkLogin() && $command !== "login") {
        header("Location: login");
        exit();
    }
    switch ($command) {
        case 'login':
        case 'loan':
        case 'chart':
        case 'transactions':
            require("Model/{$command}.php");
            require("View/{$command}.php");
            break;

        default:
            require("Model/index.php");
            require("View/index.php");
            break;
    }