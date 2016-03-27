<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    getHeader("登录");
?>
        <center>
            <form action="" method="post">
                <p id="msg" style="display: none;">密码错误</p>
                <p><label for="password">请输入密码：</label><input type="password" name="password" id="password" /></p>
                <p><input type="submit" name="submit" id="submit" value="提交" /></p>
            </form>
        </center>

        <script>
            window.onload=function() {
                if (<?=$_POST["password"]?1:0;?>) {
                    document.getElementById('msg').style["display"]="block";
                }
            };
        </script>
<?php getFooter(); ?>