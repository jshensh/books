<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    getHeader("借贷账目明细");
?>
        <p id="rdp" style="display: none;">本链接将于 <span id="rd"></span> 后失效</p>
        <div id="errmsg" style="display: none;"><p>无法访问指定账单，请联系您的债权人重新获取链接</p></div>
        <div id="detail" style="display: none;">
            <center>
                <table style="text-align: center;" border="1">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width: 67px;">日期</th>
                            <th rowspan="2">摘要</th>
                            <th colspan="11">借方</th>
                            <th colspan="11">贷方</th>
                            <th rowspan="2">借或贷</th>
                            <th colspan="12">余额</th>
                        </tr>
                        <tr>
                            <th>亿</th>
                            <th>仟</th>
                            <th>百</th>
                            <th>十</th>
                            <th>万</th>
                            <th>千</th>
                            <th>百</th>
                            <th>十</th>
                            <th style="border-right-color: red">元</th>
                            <th>角</th>
                            <th>分</th>
                            <th>亿</th>
                            <th>仟</th>
                            <th>百</th>
                            <th>十</th>
                            <th>万</th>
                            <th>千</th>
                            <th>百</th>
                            <th>十</th>
                            <th style="border-right-color: red">元</th>
                            <th>角</th>
                            <th>分</th>
                            <th>&nbsp;</th>
                            <th>亿</th>
                            <th>仟</th>
                            <th>百</th>
                            <th>十</th>
                            <th>万</th>
                            <th>千</th>
                            <th>百</th>
                            <th>十</th>
                            <th style="border-right-color: red">元</th>
                            <th>角</th>
                            <th>分</th>
                        </tr>
                    </thead>
                    <tbody id="detailLine">

                    </tbody>
                </table>
            </center>
        </div>

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
            var updateTime=function(time) {
                t=new Date(parseInt(time)*1000).getTime()-new Date().getTime();
                if (t<=0) {
                    document.getElementById("rdp").style["display"]="none";
                    return false;
                }
                var d=Math.floor(t/1000/60/60/24);
                var h=("0"+Math.floor(t/1000/60/60%24)).substr(-2);
                var m=("0"+Math.floor(t/1000/60%60)).substr(-2);
                var s=("0"+Math.floor(t/1000%60)).substr(-2);
                document.getElementById("rd").innerHTML=(d?(d+" 天 "):"")+(h!=="00"?(h+" 时 "):"")+(m!=="00"?(m+" 分 "):"")+(s+" 秒");
            };
            function num_fix(num) {
                return parseFloat(parseFloat(String(num).replace(/[^\d\-\.]/g,"")).toFixed(2));
            }
            window.onload=function() {
                var data=<?=$data;?>;
                var setName=<?=$setName;?>;
                var money=0;
                var rd=<?=$rd;?>;
                if (setName) {
                    document.getElementById("rdp").style["display"]="block";
                    updateTime(rd);
                    setInterval(function() { updateTime(rd); },1000);
                    document.getElementById('detail').style["display"]="block";
                    var getFormatMoney=function(value,needAbs) {
                        value=needAbs?Math.abs(parseFloat(value)):parseFloat(value);
                        value=!isNaN(value)?(("           "+(value.toFixed(2).replace(/\./g,""))).substr(-11)).split(""):"           ".split("");
                        var re="";
                        for (var j=0;j<value.length;j++) {
                            re+="<td"+(j==8?" style=\"border-right-color: red\"":"")+">"+value[j].replace(/ /g,"&nbsp;")+"</td>";
                        }
                        return re;
                    };
                    var addBlackLine="";
                    for (var i=0;i<data.length;i++) {
                        money+=parseFloat(data[i]["money"]);
                        money=num_fix(money);
                        document.getElementById('detailLine').innerHTML+="<tr"+addBlackLine+"><td>"+new Date(parseInt(data[i]["t"])*1000).Format("yyyy-MM-dd hh:mm:ss")+"</td><td>"+data[i]["txt"]+"</td>"+(parseFloat(data[i]["money"])<0?getFormatMoney(data[i]["money"],true):getFormatMoney("",false))+(parseFloat(data[i]["money"])>0?getFormatMoney(data[i]["money"],true):getFormatMoney("",false))+"<td>"+(parseFloat(data[i]["money"])>0?"贷":"借")+"</td><td>"+(parseFloat(data[i]["money"])>0?parseFloat(data[i]["money"]).toFixed(2):"<span style=\"color: red\">("+Math.abs(parseFloat(data[i]["money"])).toFixed(2)+")</span>")+"</td>"+getFormatMoney(money,false)+"</tr>";
                        addBlackLine=addBlackLine?"":(!money?" class=\"addBlackLine\"":"");
                    }
                } else {
                    document.getElementById('errmsg').style["display"]="block";
                }
            }
        </script>
        <style>
            table, th, td {
                border: 1px solid #3ACECE;
            }
            th {
                color: #3ACECE;
            }
            table {
                border-collapse: collapse;
                border-right: none;
                border-top: none;
            }
            th, td {
                padding: 3px;
                font-size: 13px;
                border-bottom: none;
                border-left: none;
            }
            .addBlackLine td {
                border-top-color: black !important;
            }
        </style>
<?php getFooter(); ?>