
<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
require_once ('config.php');
$embed_mode = isset($_GET['embed']) && $_GET['embed'] == '1';
?>
<!DOCTYPE html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">

    <script src="js/jquery-3.6.0.js"></script>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: <?= $embed_mode ? '#f3f7fb' : '#ffffff'; ?>;
            font-family: Arial, sans-serif;
        }

        body {
            padding: <?= $embed_mode ? '12px' : '0'; ?>;
        }

        .highcharts-figure,
        .highcharts-data-table table {
            min-width: 320px;
            max-width: 800px;
            margin: 1em auto;
        }

        #container {
            height: <?= $embed_mode ? '290px' : '325px'; ?>;
        }

        #container2 {
            height: <?= $embed_mode ? '190px' : '215px'; ?>;
        }

        .tit_monitor { text-align:center; font-size: 15px; margin:0;}

        #monitor_error {
            display: none;
            max-width: 760px;
            margin: 12px auto;
            padding: 12px 14px;
            border: 1px solid #f1b0b7;
            border-radius: 4px;
            color: #842029;
            background: #f8d7da;
            font-family: Arial, sans-serif;
            font-size: 14px;
            text-align: center;
        }

        .monitor-embed-shell {
            background: #ffffff;
            border: 1px solid #dbe5ef;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(25, 52, 77, 0.08);
            overflow: hidden;
        }

        .monitor-embed-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid #e8eef5;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .monitor-embed-title {
            margin: 0;
            color: #17324a;
            font-size: 18px;
            font-weight: 700;
        }

        .monitor-embed-subtitle {
            margin: 4px 0 0;
            color: #54708a;
            font-size: 12px;
        }

        .monitor-embed-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e8f6ed;
            color: #167342;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .monitor-embed-body {
            padding: 6px 12px 10px;
        }

    </style>

    <?php
        $query_clientes = mysqli_query($link, "SELECT nome, plano FROM sis_cliente WHERE login LIKE '$_GET[login]'");
        while($row = mysqli_fetch_array($query_clientes)){
            $cli_nome = $row['nome'];
            $cli_plano = $row['plano'];
        }
    ?>

</head>

    <body>

    <?php if ($embed_mode) { ?>
    <div class="monitor-embed-shell">
        <div class="monitor-embed-head">
            <div>
                <p class="monitor-embed-title"><?= htmlspecialchars($cli_nome); ?></p>
                <p class="monitor-embed-subtitle">PPPoE monitorado: <b><?= htmlspecialchars($_GET['login']); ?></b> | Plano: <b><?= htmlspecialchars($cli_plano); ?></b></p>
            </div>
            <div class="monitor-embed-pill">Uptime: <span id="span_uptime" style="margin-left: 6px;">--</span></div>
        </div>
        <div class="monitor-embed-body">
            <div id="monitor_error"></div>
    <?php } else { ?>
        <?php
        echo "<p class='tit_monitor'><b>Cliente:</b> $cli_nome <b>[$_GET[login]]</b><br> <b>Plano:</b> $cli_plano - <b>Uptime: </b><span id='span_uptime'></span></p> ";
        ?>
        <div id="monitor_error"></div>
    <?php } ?>
    
    <script>

    var chart;
    var chart_ping;
    var monitorInterval;

    function showMonitorError(message) {
        var monitor_error = window.document.getElementById('monitor_error');
        if (monitor_error) {
            monitor_error.innerText = message;
            monitor_error.style.display = 'block';
        }
    }

    function hideMonitorError() {
        var monitor_error = window.document.getElementById('monitor_error');
        if (monitor_error) {
            monitor_error.innerText = '';
            monitor_error.style.display = 'none';
        }
    }

    monitorInterval = setInterval(function(info){
        today=new Date();
        h=today.getHours();
        m=today.getMinutes();
        s=today.getSeconds();

        //var count = h+':'+m+':'+s; // Com concatenação
        var count = `${h}:${m}:${s}`; // Novo método

        //count++;
        $.ajax({
            url: 'api/winbox.php?login=<?php echo $_GET['login']; ?>&router=<?php echo $_GET['router']; ?>',
            type: 'GET',
            datatype: 'json',
            cache: false,
            success: function (msg) {
                //console.log(msg);

                
                //$('.teste').html(msg);
                
                var retorno = JSON.parse(msg);

                if (retorno.error) {
                    showMonitorError(retorno.message || 'Nao foi possivel conectar na API do roteador.');
                    if (monitorInterval) {
                        clearInterval(monitorInterval);
                    }
                    return;
                }

                hideMonitorError();
                // console.warn(retorno);
                var RX = parseFloat(retorno['RX']);
                var TX = parseFloat(retorno['TX']);
                
                var PING = parseFloat(retorno['PING']);

                var UPTIME = retorno['UPTIME'];

                //console.log(UPTIME);

                var span_uptime = window.document.getElementById('span_uptime');
                
                span_uptime.innerText = UPTIME;

                //var number = 1;
                next_1 = chart.series[0].data.length > 30;
                next_2 = chart.series[1].data.length > 30;

                next_ping = chart_ping.series[0].data.length > 30;

                chart.series[0].addPoint([count, RX], true, next_1);
                chart.series[1].addPoint([count, TX], true, next_2);

                chart_ping.series[0].addPoint([count, PING], true, next_ping);             

                //var log_TX = [count, TX];
                //var log_PING = [count, PING];

                //console.log(count);

                //log_RX[count] = RX;
                //log_TX[count] = TX;
                //log_PING[count] = PING;


                //console.log(log_TX);
                //console.log(log_PING);

                //console.log(log_RX);

               /* let tx_history[];
                console.log(tx_history);

                for (const item of retorno) {
                sum += RX;
                }*/

                //console.log('MSG: ' +msg);

                //console.log('Retorno: ' +retorno);

                /*
                console.log(RX);
                console.log(TX);
                console.log(PING);
                */
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Retorno caso algum erro ocorra
                console.log(errorThrown);
                }
        });
    }, 2000);

    $(document).ready(function()
    {

        chart = new Highcharts.chart('container', {
            chart: {
                type: 'area'
            },
            title: {
                text: 'Tráfego Up / Down'
            },
            yAxis: {
                title: {
                    text: 'Throughput'
                }
            },
            series: [{
                name: 'Upload (MB)',
                data: [
                    //RX,
                ], color: Highcharts.getOptions().colors[8] // color Red
            }, {
                name: 'Download (MB)',
                data: [
                    //TX,
                ], color: Highcharts.getOptions().colors[0] // color Red
            }]
        });

        chart_ping = new Highcharts.chart('container2', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Latencia'
            },
            yAxis: {
                title: {
                    text: 'ICMP (PING)'
                }
            },
            series: [{
                name: 'PING (ms)',
                data: [
                    //RX,
                ]
            }]
        });
    });


    </script>
    <figure class="highcharts-figure">
        <div id="container"></div>
        <p class="highcharts-description">

    <figure class="highcharts-figure">
        <div id="container2"></div>
        <p class="highcharts-description">
    <?php if ($embed_mode) { ?>
        </div>
    </div>
    <?php } ?>
    </body>
</html>
