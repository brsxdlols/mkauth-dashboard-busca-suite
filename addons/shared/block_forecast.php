<?php
// Read-only estimate. The native financial routine remains responsible for blocking.
function mka_block_forecast($client, $titles, $options, $today) {
    $count = (int)$titles['open_count'];
    $due = $titles['oldest_due'];
    $days = isset($client['dias_corte']) && is_numeric($client['dias_corte']) ? (int)$client['dias_corte'] : null;
    $result = array('open_count'=>$count, 'oldest_due'=>$due, 'cut_days'=>$days,
        'cut_date'=>null, 'eligible_date'=>null, 'eligible_now'=>false,
        'automatic_block'=>isset($options['auto_corte']) ? $options['auto_corte'] : null,
        'automatic_unlock'=>isset($options['auto_desbloqueio']) ? $options['auto_desbloqueio'] : null,
        'observation'=>$client['observacao']==='sim', 'exempt'=>$client['isento']==='sim',
        'active'=>$client['cli_ativado']==='s', 'estimate'=>true);
    if ($count > 0 && $due && $days !== null && $days >= 0) {
        $date = DateTime::createFromFormat('!Y-m-d', substr($due,0,10));
        if ($date && $date->format('Y-m-d')===substr($due,0,10)) {
            $date->modify('+'.$days.' days');
            $result['cut_date']=$date->format('Y-m-d');
            // "More overdue days than the cutoff": eligibility starts after the cutoff day.
            $date->modify('+1 day');
            $result['eligible_date']=$date->format('Y-m-d');
            $result['eligible_now']=$result['eligible_date'] <= $today;
        }
    }
    return $result;
}
