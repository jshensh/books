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
                    for (true;lastDate<nowDate;lastDate+=86400000) {
                        yezs.push([
                            lastDate, // the date
                            parseFloat(lastClose), // open
                            parseFloat(lastClose), // high
                            parseFloat(lastClose), // low
                            parseFloat(lastClose) // close
                        ]);
                    }
                    lastDate=nowDate;
                    yezs.push([
                        nowDate, // the date
                        parseFloat(lastClose), // open
                        parseFloat(statements[i]["high"]), // high
                        parseFloat(statements[i]["low"]), // low
                        parseFloat(statements[i]["closed"]) // close
                    ]);
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
                console.log(yezs);
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
                        lineWidth: 2
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
                        name: 'RMB',
                        data: yezs,
                        dataGrouping: {
                            units: groupingUnits
                        }
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

        <div id="container" style="height: 400px; min-width: 310px"></div>
<?php getFooter(); ?>