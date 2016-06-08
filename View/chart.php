<?php
    if (!defined("ROOT_DIR")) {
        echo "Access Denied!";
        exit();
    }
    getHeader("余额变化走势");
?>
        <script src="http://cdn.hcharts.cn/jquery/jquery-1.8.3.min.js"></script>
        <script src="http://cdn.hcharts.cn/highstock/highstock.js"></script>
        <script type="text/javascript">
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
            $(function () {
                var statements=<?=$statements;?>,
                    yezs=[],
                    income=[],
                    expend=[],
                    expendSum=0,
                    groupingUnits = [[
                        'week',
                        [1]
                    ], [
                        'month',
                        [1]
                    ]],
                    lastClose=0.00,
                    lastDate=parseInt(statements[0]["t"])*1000+28800000;
                for (var i=0; i < statements.length; i++) {
                    var nowDate=parseInt(statements[i]["t"])*1000+28800000;
                    for (true;lastDate<nowDate-86400000;lastDate+=86400000) {
                        yezs.push([
                            lastDate, // the date
                            parseFloat(lastClose), // open
                            parseFloat(lastClose), // high
                            parseFloat(lastClose), // low
                            parseFloat(lastClose) // close
                        ]);
                        income.push([lastDate,0]);
                        expend.push([lastDate,0]);
                    }
                    lastDate=nowDate;
                    yezs.push([
                        nowDate, // the date
                        parseFloat(lastClose), // open
                        parseFloat(statements[i]["high"]), // high
                        parseFloat(statements[i]["low"]), // low
                        parseFloat(statements[i]["closed"]) // close
                    ]);
                    income.push([lastDate,parseFloat(statements[i]["income"])]);
                    expend.push([lastDate,parseFloat(statements[i]["expend"])]);
                    expendSum+=parseFloat(statements[i]["expend"]);
                    lastClose=statements[i]["closed"];
                }
                var todayT=new Date().getTime()-(new Date().getTime()%86400)+28800000;
                lastDate+=86400000;
                while (true) {
                    if (lastDate>=todayT) {
                        break;
                    }
                    yezs.push([
                        lastDate, // the date
                        parseFloat(lastClose), // open
                        parseFloat(lastClose), // high
                        parseFloat(lastClose), // low
                        parseFloat(lastClose) // close
                    ]);
                    lastDate+=86400000;
                }
                console.log(yezs,expendSum);
                $('#container').highcharts('StockChart', {

                    rangeSelector: {
                        selected: 1
                    },

                    title: {
                        text: '余额变化走势'
                    },
                    yAxis: [{
                        labels: {
                            align: 'right',
                            x: -3
                        },
                        title: {
                            text: '余额'
                        },
                        lineWidth: 2,
                        height: "59%"
                    },{
                        labels: {
                            align: 'right',
                            x: -3
                        },
                        title: {
                            text: '收入'
                        },
                        lineWidth: 2,
                        top: "60%",
                        height: "19%",
                        offset: 0,
                        max: 5000
                    },{
                        labels: {
                            align: 'right',
                            x: -3
                        },
                        title: {
                            text: '支出'
                        },
                        lineWidth: 2,
                        top: "81%",
                        height: "19%",
                        offset: 0,
                        plotLines : [{
                            value : Math.round(expendSum/expend.length*100)/100,
                            color : 'red',
                            dashStyle : 'shortdash',
                            width : 2,
                            label : {
                                text : '支出平均 '+Math.round(expendSum/expend.length*100)/100
                            }
                        }]
                    }],
                    plotOptions: {
                        candlestick: {
                            color: '#33AA11',
                            upColor: '#DD2200',
                            lineColor: '#33AA11',
                            upLineColor: '#DD2200',
                        }  
                    },  
                    series: [{
                        type: 'candlestick',
                        name: '余额',
                        data: yezs,
                        dataGrouping: {
                            units: groupingUnits
                        }
                    },{
                        name: '收入',
                        data: income,
                        dataGrouping: {
                            units: groupingUnits
                        },
                        yAxis: 1
                    },{
                        name: '支出',
                        data: expend,
                        dataGrouping: {
                            units: groupingUnits
                        },
                        yAxis: 2
                    }],
                    credits: {
                        enabled: false
                    },
                    exporting: {
                        enabled: false
                    }
                });
            });
        </script>

        <style>
            html, body {
                height: 98%;
            }
        </style>

        <div id="container" style="height: 100%; min-width: 310px"></div>
<?php getFooter(); ?>