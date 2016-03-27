<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    function getHeader($title) {
        require(ROOT_DIR."/template/header.php");
    }

    function getFooter() {
        require(ROOT_DIR."/template/footer.php");
    }