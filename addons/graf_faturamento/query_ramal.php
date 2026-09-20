<!-- 
    RELAT�RIO DE FATURAMENTO
    AUTOR: ALEFF MEYKSON
    CONTATO: (82) 9 8748-1848
    EMAIL: meyknho@gmail.com  
    @AJF TELECOM - TODOS DIREITOS RESERVADOS.
-->
<?php
                for($i=$per_ini; $i <= $per_meses; $i++){
                    if ($i<10){
                        $i="0$i";
                    }
            
                    /*$query_mes[$i] = mysql_query("SELECT sum(a.valorpag) tot_pago FROM sis_lanc a
                    WHERE  
                    a.login
                    = (SELECT username FROM radacct WHERE nasipaddress LIKE '$key' AND username = a.login AND acctstarttime LIKE '$ano-$i%' ORDER BY radacctid DESC LIMIT 1) AND 
                    a.datapag LIKE '$ano-$i%' 
                    ORDER BY a.id");*/

                    $query_mes[$i] = mysqli_query($link, "SELECT sum(a.valorpag) tot_pago FROM sis_lanc a
                    WHERE a.login 
                    IN (SELECT DISTINCT username FROM radacct WHERE nasipaddress LIKE '$key' AND acctstarttime LIKE '$ano-$i%' ORDER BY radacctid DESC)
                    AND 
                    a.datapag LIKE '$ano-$i%'
                    ORDER BY a.id");
    
                    //                    

                    //$num_resultados += mysql_num_rows($query_mes[$i]);

                    if(!$query_mes[$i]){
                        echo mysqli_error($link);
                        echo "</br>";
                    }
            
                    while ($row = mysqli_fetch_array($query_mes[$i])){
                        //$num_titulo[$i] = $row['id'];
                        //$data_pagamento[$i] = $row['datapag'];
                        $entrada[$i] = $row['tot_pago'];
                        //$ramal_[$i] = $row['nasipaddress'];
                        //$login[$i] = $row['login'];
                        
                        //$tot_entrada_[$i] += $entrada[$i];
            
                        /*echo "
                            <tr>
                            <td>$num_titulo[$i]</td>
                            <td>$login[$i]</td>
                            <td>$data_pagamento[$i]</td>
                            <td>$entrada[$i]</td>
                            </tr>
                        ";*/
            
                            $fat_por_ramal[$i][$value] = $entrada[$i];

                        }
                        $fat_por_ramal[$i][$value] = round($fat_por_ramal[$i][$value]);

                        $fat_por_ramal_ano[$value] += $fat_por_ramal[$i][$value];
            
                        /*echo "
                            <tr style='background:#000; color:#FFF;'>
                            <td></td>
                            <td></td>
                            <td>Total: </td>
                            <td>$fat_por_ramal[$i][$value]</td>
                            </tr>
                        ";*/

                    }

                    

                    /*echo "Resultados encontrados em $value = $num_resultados</br>";
                    $num_resultados = 0;*/

                ?>