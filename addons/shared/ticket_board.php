<?php
function mka_ticket_timing($visit,$now=null) {
    $now=$now===null?time():$now;
    $stamp=$visit && strpos((string)$visit,'0000-00-00')!==0?strtotime($visit):false;
    if($stamp===false)return array('state'=>'unscheduled','class'=>'is-neutral','text'=>'Sem agendamento');
    $difference=$stamp-$now;$minutes=max(1,(int)floor(abs($difference)/60));
    $hours=(int)floor($minutes/60);$days=(int)floor($hours/24);
    $duration=$days>0?$days.'d '.($hours%24).'h':($hours>0?$hours.'h '.($minutes%60).'min':$minutes.'min');
    if($difference<0)return array('state'=>'late','class'=>'is-red','text'=>'Atrasado há '.$duration);
    if($difference<=86400)return array('state'=>'upcoming','class'=>'is-orange','text'=>$difference===0?'Visita agora':'Visita em '.$duration);
    return array('state'=>'scheduled','class'=>'is-blue','text'=>'Agendada · em '.$duration);
}
