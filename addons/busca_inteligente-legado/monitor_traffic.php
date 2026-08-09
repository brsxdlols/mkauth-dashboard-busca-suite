
<?php
// INCLUE FUNCOES DE ADDONS -----------------------------------------------------------------------
require_once ('config.php');
?>
<!DOCTYPE html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">

    <script src="js/jquery-3.6.0.js"></script>

    <script src="js/highcharts.js"></script>
    <script src="js/exporting.js"></script>

    <style>
        .highcharts-figure,
        .highcharts-data-table table {
            min-width: 320px;
            max-width: 800px;
            margin: 1em auto;
        }

        #container {
            height: 325px;
        }

        #container2 {
            height: 215px;
        }

        .tit_monitor { text-align:center; font-size: 15px; margin:0;}

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

    <?php
    echo "<p class='tit_monitor'><b>Cliente:</b> $cli_nome <b>[$_GET[login]]</b><br> <b>Plano:</b> $cli_plano - <b>Uptime: </b><span id='span_uptime'></span></p> ";
    ?>
    
    <script>

    var chart;
    var chart_ping;

    setInterval(function(info){
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
    </body>
</html>