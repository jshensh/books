<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }
    getHeader("资产变化明细");
?>
        <ol id="list"></ol>
        <p>共计<span id="total"></span></p>
        <p id="changeDay"></p>
        <script>
            Date.prototype.Format = function(fmt) {
                var o = {
                    "M+" : this.getMonth()+1,
                    "d+" : this.getDate(),
                    "h+" : this.getHours(),
                    "m+" : this.getMinutes(),
                    "s+" : this.getSeconds(),
                    "q+" : Math.floor((this.getMonth()+3)/3),
                    "S"  : this.getMilliseconds()
                };
                if(/(y+)/.test(fmt))
                    fmt=fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
                for(var k in o)
                    if(new RegExp("("+ k +")").test(fmt))
                        fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
                return fmt;
            };
            Object.prototype.indexOf = function(val) {
                for (var i in this) {
                    if (this[i] == val) return i;
                }
                return -1;
            };
            window.onload=function() {
                var transactModeTmp=<?=$transactMode;?>,
                    transactions=<?=$transactions;?>,
                    transactMode={},
                    mode=isNaN(parseInt('<?=$mode;?>'))?0:parseInt('<?=$mode;?>');
                for (var i=0;i<transactModeTmp.length;i++) {
                    transactMode[transactModeTmp[i]["id"]]=transactModeTmp[i]["name"];
                }
                var totalM=0;
                for (var i=0;i<transactions.length;i++) {
                    document.getElementById('list').innerHTML+="<li>"+(new Date(parseInt(transactions[i]["t"])*1000).Format("yyyy-MM-dd hh:mm:ss"))+(transactions[i]["txt"]!=="支出" && transactions[i]["txt"]!=="收入"?" 因 "+transactions[i]["txt"]:"")+" "+(parseFloat(transactions[i]["money"])<0?"支出":"收入")+" "+Math.abs(parseFloat(transactions[i]["money"])).toFixed(2)+" 元"+(mode==0?"("+transactMode[transactions[i]["transactMode"]]+")":"")+"</li>";
                    var matchV=transactions[i]["txt"].match("^(.*?)(充值|提现)$");
                    totalM+=(matchV && transactMode.indexOf(matchV[1]))?0:parseFloat(transactions[i]["money"]);
                }
                document.getElementById('total').innerHTML=(totalM>0?"收入":(totalM<0?"支出":""))+" "+Math.abs(totalM).toFixed(2)+" 元";
                document.getElementById('changeDay').innerHTML="<a href=\"?t=<?=($t-86400).($_GET["transactMode"]?"&transactMode={$_GET["transactMode"]}":"");?>\">前一天</a>&nbsp;<?=strtotime(date("Y-m-d"))-$t>0?("<a href=\\\"?t=".($t+86400).($_GET["transactMode"]?"&transactMode={$_GET["transactMode"]}":"")."\\\">后一天</a>"):"";?>";
            };
        </script>
<?php getFooter(); ?>