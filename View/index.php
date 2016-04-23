<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    getHeader("账簿");
?>
        <center>
            <form action="index" method="post">
                <p id="msg" style="display: none;"></p>
                <p><select name="transactMode" id="transactMode"></select></p>
                <p id="txtP"><label for="txt">备注：</label><input type="text" name="txt" id="txt" /></p>
                <p><label for="money">金额：</label><input type="text" name="money" id="money" /></p>
                <p id="loanP"><input type="checkbox" name="loan" id="loan" value="true" /><label for="loan" id="loanL">借出</label></p>
                <p id="nameP" style="display: none;"><label for="name">姓名：</label><input type="text" name="name" id="name" /></p>
                <input type="hidden" name="token" id="token" value="<?=$token; ?>" />
                <p><input type="submit" name="send" id="send" value="提交" /></p>
            </form>
            <p id="todayTotal"></p>
            <table border="0"><tbody id="balance"></tbody></table>
            <p>当前时间：<span id="time"></span></p>
        </center>

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
            Array.prototype.indexOf = function(val) {
                for (var i = 0; i < this.length; i++) {
                    if (this[i] == val) return i;
                }
                return -1;
            };
            var updateTime=function() {
                document.getElementById("time").innerHTML=new Date().Format("yyyy-MM-dd hh:mm:ss");
            };
            var newDom=function(tag,id,innerHTML,name,value) {
                var re=document.createElement(tag);
                id!=null && (re.id=id);
                name!=null && (re.name=name);
                value!=null && (re.value=value);
                innerHTML!=null && (tag==="optgroup"?re.label=innerHTML:re.appendChild(typeof innerHTML==="object"?innerHTML:document.createTextNode(innerHTML)));
                return re;
            };
            var updateTransactModeList=function(list) {
                var dom={};
                dom["pay"]=[],dom["sk"]=[],dom["topUp"]=[],dom["withdrawal"]=[];
                for (var i in list) {
                    if (typeof list[i]==="object") {
                        list[i]["topUp"]==="1" && list[i]["id"]!=="1" && dom["topUp"].push([list[i]["id"],i]);
                        list[i]["withdrawal"]==="1" && list[i]["id"]!=="1" && dom["withdrawal"].push([list[i]["id"],i]);
                        list[i]["sk"]==="1" && dom["sk"].push([list[i]["id"],i]);
                        list[i]["pay"]==="1" && dom["pay"].push([list[i]["id"],i]);
                    }
                }
                optGroup={"pay":"支出","sk":"收入","topUp":"充值","withdrawal":"提现"};
                opgValue={"pay":"#tt1_0","sk":"0_#tt1","topUp":"1_#tt1","withdrawal":"#tt1_1"};
                for (var transactionType in dom) {
                    var tmpOptGroup=newDom("optgroup",null,optGroup[transactionType]);
                    for (var id in dom[transactionType]) {
                        tmpOptGroup.appendChild(newDom("option",null,list[dom[transactionType][id][1]]["name"]+optGroup[transactionType],null,opgValue[transactionType].replace(/#tt1/,dom[transactionType][id][0])));
                    }
                    document.getElementById('transactMode').appendChild(tmpOptGroup);
                }
            };
            window.onload=function() {
                updateTime();
                updateTransactModeList(<?=$transactModeList;?>);
                document.getElementById('transactMode').onchange=function() {
                    var opg=this.value.match(/^(\d+_1|1_\d+)$/) && this.value!=="1_0" && this.value!=="0_1";
                    document.getElementById('txtP').style["display"]=opg?"none":"block";
                    document.getElementById('loanP').style["display"]=opg?"none":"block";
                    document.getElementById('nameP').style["display"]=opg?"none":(document.getElementById('loan').checked && "block");
                    if (opg) {
                        document.getElementById('txt').value=this.options[this.options.selectedIndex].text;
                    } else {
                        document.getElementById('txt').value="";
                    }
                    document.getElementById('loanL').innerHTML=this.value.match(/^(\d+_0)$/)?"借出":"贷入";
                };
                document.getElementById('loan').onclick=function() {
                    if (this.checked) {
                        document.getElementById('nameP').style["display"]="block";
                    } else {
                        document.getElementById('nameP').style["display"]="none";
                    }
                }
                setInterval(updateTime, 1000);
                var postStatus=<?=$op;?>;
                var rollbackStatus=<?=$rollbackStatus===NULL?"null":($rollbackStatus?1:0);?>;
                if (rollbackStatus!==null) {
                    document.getElementById('msg').innerHTML="撤销"+(rollbackStatus?"成功":"失败");
                    document.getElementById('msg').style["display"]="block";
                }
                if (postStatus>-1) {
                    document.getElementById('msg').innerHTML="插入"+(postStatus?"成功&nbsp;<a href=\"?rollback=true\">撤销</a>":"失败");
                    document.getElementById('msg').style["display"]="block";
                }
                var amount=<?=$amount;?>;
                var loanSum=<?=$loanSum;?>["s"];
                var todayTotal=<?=$todayTotal;?>["closed"];
                document.getElementById('todayTotal').innerHTML="今日总计：<a href=\"chart\" target=\"_blank\">"+parseFloat(todayTotal).toFixed(2)+"</a>";
                var balanceTmp=[];
                var allBalance=0;
                for (var i in amount) {
                    balanceTmp.push("<tr><td style=\"text-align: right\">"+amount[i]["name"]+"余额：</td><td><a href=\"transactions?transactMode="+amount[i]["id"]+"\" target=\"_blank\">"+parseFloat(amount[i]["s"]).toFixed(2)+"</a></td></tr>");
                    allBalance+=parseFloat(amount[i]["s"]);
                };
                balanceTmp.push("<tr><td style=\"text-align: right\">总计：</td><td><a href=\"transactions\" target=\"_blank\">"+parseFloat(allBalance).toFixed(2)+"</a></td></tr>");
                balanceTmp.push("<tr><td style=\"text-align: right\">借款：</td><td><a href=\"loan\" target=\"_blank\">"+parseFloat(loanSum).toFixed(2)+"</a></td></tr>");
                document.getElementById('balance').innerHTML+=balanceTmp.join("");
            };
        </script>
<?php getFooter(); ?>