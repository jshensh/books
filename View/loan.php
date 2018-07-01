<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }

    getHeader("借贷账目明细");
?>
        <div id="allList" style="display: none;"></div>
        <div id="detail" style="display: none;">
            <center>
                <table style="text-align: center;">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width: 67px;">日期</th>
                            <th rowspan="2">摘要</th>
                            <th rowspan="2" style="min-width: 27px;">交易方式</th>
                            <th colspan="11">借方</th>
                            <th colspan="11">贷方</th>
                            <th rowspan="2">借或贷</th>
                            <th colspan="12">余额</th>
                            <th rowspan="2">积数 / <br />(元·日)</th>
                            <th rowspan="2">积数计算结果</th>
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
                <p><a href="####" onclick="doDelete();">销账</a>&nbsp;&nbsp;&nbsp;<a href="####" onclick="getShareLink();">分享账单给债务人</a><span id="interestSpan">&nbsp;&nbsp;&nbsp;<a href="####" onclick="interest();">结息</a></span></p>
                <form action="" method="post" id="del">
                    <input type="hidden" name="delete" value="true" />
                </form>
            </center>
        </div>

        <script src="http://cdn.hcharts.cn/jquery/jquery-1.8.3.min.js"></script>
        <script>
            var data=<?=$data;?>;
            var setName=<?=$setName;?>;
            var sumMoney=0;
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
            Array.prototype.sum = function() {
                var re=0;
                for (var i=0;i<this.length;i++) {
                    re+=~~this[i];
                }
                return re;
            };
            function num_fix(num) {
                return parseFloat(parseFloat(String(num).replace(/[^\d\-\.]/g,"")).toFixed(2));
            };
            var doDelete=function() {
                var name=<?=json_encode($_GET["name"]);?>;
                if (prompt("您正要进行销账操作，该操作无法撤回。在您正确输入“"+name+"”后才可继续操作")==name) {
                    document.getElementById('del').submit();
                } else {
                    alert("已取消操作");
                }
            };
            var getShareLink=function() {
                $.ajax({
                    type: 'post',
                    url: location.href,
                    dataType: 'json',
                    timeout: 5000,
                    data: {"shareTime": prompt("请输入链接有效时长（分钟）：","30"), "path": location.origin+location.pathname.match(/.*\//)},
                    success: function(re) {
                        if (re["status"]==="success") {
                            prompt("复制以下链接",re["link"]);
                        } else {
                            alert("请求失败");
                        }
                    },
                    error: function(XMLHttpRequest,status) {
                        alert("请求失败");
                    }
                });
            };
            var strtotime=function(datetime) { 
                var tmp_datetime = datetime.replace(/:/g,'-');
                tmp_datetime = tmp_datetime.replace(/ /g,'-');
                var arr = tmp_datetime.split("-");
                var now = new Date(Date.UTC(arr[0],arr[1]-1,arr[2],arr[3]-8,arr[4],arr[5]));
                return now.getTime();
            }
            window.onload=function() {
                if (setName) {
                    document.getElementById('detail').style["display"] = "block";
                    var getFormatMoney = function(value, needAbs) {
                        value = needAbs ? Math.abs(parseFloat(value)) : parseFloat(value);
                        value = !isNaN(value) ? (("           "+(value.toFixed(2).replace(/\./g,""))).substr(-11)).split("") : "           ".split("");
                        var re = "";
                        for (var j = 0; j < value.length; j++) {
                            re += "<td" + (j == 8 ? " style=\"border-right-color: red\"" : "") + ">" + value[j].replace(/ /g,"&nbsp;") + "</td>";
                        }
                        return re;
                    };

                    var groupedData = [[]];
                    for (var i = 0; i < data.length; i++) {
                        sumMoney += parseFloat(data[i]["money"]);
                        sumMoney = num_fix(sumMoney);

                        groupedData[groupedData.length ? groupedData.length - 1 : 0].push({
                            'id':       i,
                            'name':     data[i]['name'],
                            'money':    data[i]['money'],
                            'txt':      data[i]['txt'],
                            't':        data[i]['t'],
                            'sumMoney': sumMoney,
                            'name':     data[i]['name']
                        });

                        if (!sumMoney && i !== data.length - 1) {
                            groupedData.push([]);
                        }
                    }

                    var detailTrTemplate = "<tr{{__addRedLine__}} id=\"account_{{__detailGroupNo__}}_{{__detailLineNo__}}\"{{__isShow__}}>\n\
    <td>{{__formattedTime__}}</td>\n\
    <td>{{__txt__}}</td>\n\
    <td>{{__name__}}</td>\n\
    {{__formattedDebit__}}\n\
    {{__formattedCredit__}}\n\
    <td>{{__side__}}</td>\n\
    <td>{{__formattedDrCrMoney__}}</td>\n\
    {{__formattedSumMoney__}}\n\
    <td>{{__jishuFormula__}}</td>\n\
    <td>{{__jishuResult__}}</td>\n\
</tr>";
                    
                    var detailLineData = '';

                    for (var i = 0; i < groupedData.length; i++) {
                        var isShow = (i > groupedData.length - 3);

                        if (!isShow) {
                            detailLineData += '<tr ' + (i !== 0 ? ' class="addRedLine"' : '') + ' style="cursor: pointer;" maxAccount=' + groupedData[i].length + ' id="account_' + i + '" onclick="showHiddenAccount(this);"><td colspan="40">已折叠 ' + new Date(parseInt(groupedData[i][0]["t"]) * 1000).Format("yyyy-MM-dd hh:mm:ss") + ' 至 ' + new Date(parseInt(groupedData[i][groupedData[i].length - 1]["t"]) * 1000).Format("yyyy-MM-dd hh:mm:ss") + ' 的账目，点击本行展开</td></tr>';
                        }

                        for (var j = 0; j < groupedData[i].length; j++) {
                            if (!(i === 0 && j === 0)) {
                                var jishu = ~~num_fix(sumMoney - parseFloat(groupedData[i][j]["money"])),
                                jishuDay = ~~(~~groupedData[i][j]["t"] / 86400) - ~~(~~data[groupedData[i][j]['id'] - 1]["t"] / 86400);
                            }

                            var formattedTime = new Date(~~groupedData[i][j]["t"] * 1000).Format("yyyy-MM-dd hh:mm:ss"),
                                formattedDebit = (parseFloat(groupedData[i][j]["money"]) < 0 ? getFormatMoney(groupedData[i][j]["money"], true) : getFormatMoney("", false)),
                                formattedCredit = (parseFloat(groupedData[i][j]["money"]) > 0 ? getFormatMoney(groupedData[i][j]["money"], true) : getFormatMoney("", false)),
                                side = (parseFloat(groupedData[i][j]["money"]) > 0 ? "贷" : "借"),
                                formattedDrCrMoney = (parseFloat(groupedData[i][j]["money"]) > 0 ? parseFloat(groupedData[i][j]["money"]).toFixed(2) : "<span style=\"color: red\">(" + Math.abs(parseFloat(groupedData[i][j]["money"])).toFixed(2) + ")</span>"),
                                formattedSumMoney = getFormatMoney(groupedData[i][j]["sumMoney"], false),
                                jishuFormula = (!jishu ? "/" : (jishu + " × " + jishuDay)),
                                jishuResult = (!jishu ? "/" : num_fix(jishu * jishuDay));

                            detailLineData += detailTrTemplate
                                .replace(/\{\{__addRedLine__\}\}/g, (i !== 0 && j === 0) ? ' class="addRedLine"' : '')
                                .replace(/\{\{__isShow__\}\}/g, !isShow ? ' style="display: none;"' : '')
                                .replace(/\{\{__detailGroupNo__\}\}/g, i)
                                .replace(/\{\{__detailLineNo__\}\}/g, j)
                                .replace(/\{\{__formattedTime__\}\}/g, formattedTime)
                                .replace(/\{\{__txt__\}\}/g, groupedData[i][j]["txt"])
                                .replace(/\{\{__name__\}\}/g, groupedData[i][j]["name"])
                                .replace(/\{\{__formattedDebit__\}\}/g, formattedDebit)
                                .replace(/\{\{__formattedCredit__\}\}/g, formattedCredit)
                                .replace(/\{\{__formattedDrCrMoney__\}\}/g, formattedDrCrMoney)
                                .replace(/\{\{__side__\}\}/g, side)
                                .replace(/\{\{__formattedSumMoney__\}\}/g, formattedSumMoney)
                                .replace(/\{\{__jishuFormula__\}\}/g, jishuFormula)
                                .replace(/\{\{__jishuResult__\}\}/g, jishuResult);
                        }
                    }

                    document.getElementById('detailLine').innerHTML = detailLineData;
                } else {
                    document.getElementById('allList').style["display"] = "block";
                    var toHtml = "<ol>";
                    for (var i = 0; i < data.length; i++) {
                        toHtml += "<li><a href=\"?name=" + encodeURIComponent(data[i]["name"]) + "\">" + new Date(parseInt(data[i]["minT"]) * 1000).Format("yyyy-MM-dd hh:mm:ss") + " 起共"+(parseFloat(data[i]["all"]) > 0 ? "从" : "向") + " " + data[i]["name"] + " " + (parseFloat(data[i]["all"]) > 0 ? "贷入" : "借出") + " " + Math.abs(parseFloat(data[i]["all"])).toFixed(2) + " 元</a></li>";
                        sumMoney += parseFloat(data[i]["all"]);
                    }
                    document.getElementById('allList').innerHTML = toHtml + "<p>共计 " + sumMoney.toFixed(2) + " 元</p></ol>";
                }
            };
            var showHiddenAccount=function(ele) {
                var j=parseInt(ele.getAttribute("maxAccount"));
                for (var k=0;k<j;k++) {
                    document.getElementById(ele.getAttribute("id")+"_"+k).style["display"]="table-row";
                }
                ele.remove();
            };
            var interest=function() {
                var rate=parseFloat(prompt("请输入年利率（5.2%=0.052）","0.052")),date=prompt("请输入起始结算日期",new Date().Format("yyyy-MM-dd"));
                var startDom=$("#detailLine tr td:contains('"+date+"'):first");
                if (!rate || !startDom.length) {
                    return false;
                }
                var firstChargeNumber=~~startDom.parent().find("td:eq(39)").html();
                var chargeNumber=firstChargeNumber?[firstChargeNumber]:[],doms=startDom.parent().nextAll().find("td:eq(39)");
                for (var i=0;i<doms.length;i++) {
                    if (~~doms[i].innerHTML) {
                        chargeNumber.push(~~doms[i].innerHTML);
                    }
                }
                var lastDay=~~(~~(new Date().getTime()/1000/86400)-~~(data[data.length-1]["t"]/86400))*~~sumMoney;
                if (lastDay) {
                    chargeNumber.push(lastDay);
                }
                $("#detailLine").append($('<tr><td colspan="40" style="text-align: right; word-break: break-all;">自 '+date+' 起共计息 ('+chargeNumber.join("+")+')*'+rate+'/365='+(~~(chargeNumber.sum()*rate/365*100)/100)+' 元</td></tr>'));
                $("#interestSpan").remove();
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
            .addRedLine td {
                border-top-color: red !important;
            }
        </style>
<?php getFooter(); ?>