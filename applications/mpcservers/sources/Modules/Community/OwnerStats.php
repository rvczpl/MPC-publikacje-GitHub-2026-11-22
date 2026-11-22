<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Community\Owner;
use IPS\mpcservers\Modules\Community\OwnerAccess;
use IPS\mpcservers\Modules\Community\TopicViews;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class OwnerStats
{
 public static function allowed($tid){return OwnerAccess::isOwner($tid,\IPS\Member::loggedIn()->member_id);}
 public static function url($tid){return Owner::url(array('do'=>'stats','id'=>(int)$tid));}
 public static function linked(){return OwnerAccess::ids(\IPS\Member::loggedIn()->member_id);}
 public static function render($tid){
  if(!static::allowed($tid))throw new \OutOfRangeException;
  $topic=Presentation::topic($tid);$meta=Presentation::get($tid);
  if(!$topic||!$meta||!Presentation::visible($topic,$meta))throw new \OutOfRangeException;
  $e=array('IPS\\mpcservers\\Directory','esc');$zone=new \DateTimeZone('Europe/Warsaw');$today=new \DateTimeImmutable('today',$zone);
  $options=array('1'=>'Dzień','3'=>'3 dni','7'=>'7 dni','14'=>'14 dni','30'=>'1 miesiąc','180'=>'6 miesięcy','365'=>'1 rok');
  $pick=(string)\IPS\Request::i()->range;if(!isset($options[$pick]))$pick='14';
  $starts=array();foreach($options as $key=>$label)$starts[$key]=$today->modify('-'.((int)$key-1).' days')->getTimestamp();
  $since=(int)\IPS\Settings::i()->sps_rankings_started;$counts=array_fill_keys(array_keys($options),0);$daily=array();$scores=array();
  // Hour buckets preserve Polish calendar days across DST without requiring MySQL timezone tables.
  $where=array('created>=? AND created<?',min($starts),time()+1);
  foreach(\IPS\Db::i()->select('tid,game,FLOOR(created/3600) AS hour_bucket,COUNT(*) AS total','mpcservers_boost_events',$where,NULL,NULL,array('tid','game','hour_bucket')) as $row){
   $time=(int)$row['hour_bucket']*3600;$day=(new \DateTimeImmutable('@'.$time))->setTimezone($zone)->format('Y-m-d');$n=(int)$row['total'];
   if((int)$row['tid']===(int)$tid){$daily[$day]=($daily[$day]??0)+$n;foreach($starts as $key=>$start)if($time>=$start)$counts[$key]+=$n;}
   if($row['game']===$meta['category'])$scores[$day][(int)$row['tid']]=($scores[$day][(int)$row['tid']]??0)+$n;
  }
  $featured=array_fill_keys(iterator_to_array(\IPS\Db::i()->select('tid','mpcservers_presentations',array('featured=1'))),TRUE);
  foreach($scores as &$dayScores)$dayScores=array_diff_key($dayScores,$featured);unset($dayScores);
  $points=array();$start=(new \DateTimeImmutable('@'.$starts[$pick]))->setTimezone($zone);
  for($at=$start;$at<=$today;$at=$at->modify('+1 day')){$day=$at->format('Y-m-d');$n=$daily[$day]??0;$complete=$since && $at->getTimestamp()>=$since;$rank=NULL;if($complete&&$n>0&&isset($scores[$day][$tid])){$rank=1;foreach($scores[$day] as $votes)if($votes>$scores[$day][$tid])$rank++;}$points[]=array('date'=>$at->format('d.m.Y'),'votes'=>$complete?$n:NULL,'rank'=>$rank);}
  $out='<section class="sps-directory ipsBox i-padding_3"><header class="sps-list-heading"><h1>Statystyki serwera</h1>'.Directory::menu($meta['category']).'</header><h2>'.$e($topic->title).'</h2><p>Dostęp wyłącznie dla aktualnie oznaczonych właścicieli posiadających konto forum.</p><div class="sps-stats-counters">';
  foreach($options as $key=>$label)$out.='<a href="'.$e(static::url($tid)->setQueryString('range',$key)).'"'.($pick===(string)$key?' aria-current="page"':'').'><span>'.$label.'</span><strong>'.$counts[$key].'</strong><small>'.($since&&$starts[$key]>=$since?'podbić':'niepełna historia').'</small></a>';
  $out.='</div><p>Okresy obejmują bieżący dzień; miesiąc = 30 dni, 6 miesięcy = 180 dni, rok = 365 dni. Historia jest zbierana od '.($since?$e((new \DateTimeImmutable('@'.$since))->setTimezone($zone)->format('d.m.Y H:i')):'braku danych').'. Niepełne dni mają oznaczenie „brak danych”.</p>';
  $out.=TopicViews::section($tid,$starts,$options,$pick,$today);
  $out.='<h2>Podbicia dziennie - '.$options[$pick].'</h2>'.static::chart($points,'votes').'<h2>Pozycja dzienna według liczby podbić</h2><p>Odtworzona z zapisanych głosów w tej samej grze. Remisy zajmują wspólne miejsce. To zestawienie liczby podbić, nie historyczna kolejność listy ani tytuły serwera dnia. Serwery aktualnie oznaczone MPC Poleca są wykluczone. Historyczne zmiany polecenia nie są odwzorowywane. Dzień bez głosów nie ma pozycji.</p>'.static::chart($points,'rank');
  $out.='<details><summary>Dane dzienne</summary><div class="sps-table-wrap"><table class="ipsTable sps-server-table"><thead><tr><th>Data</th><th>Podbicia</th><th>Pozycja</th></tr></thead><tbody>';
  foreach(array_reverse($points) as $p)$out.='<tr><td data-label="Data">'.$p['date'].'</td><td data-label="Podbicia">'.($p['votes']??'Brak danych').'</td><td data-label="Pozycja">'.($p['rank']??'-').'</td></tr>';
  return $out.'</tbody></table></div></details></section>'.Directory::assets().'<style>.sps-stats-counters{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin:24px 0}.sps-stats-counters a{padding:16px;border:1px solid #8885;border-radius:10px;color:inherit}.sps-stats-counters a[aria-current]{border-color:#929aff;background:#828bff22}.sps-stats-counters strong{display:block;font-size:26px;margin:8px 0}.sps-stats-chart{width:100%;height:auto;max-height:300px;border:1px solid #8884;border-radius:10px;margin:12px 0 24px}.sps-stats-chart text{fill:currentColor;font-size:12px}.sps-stats-chart circle{fill:#99a5ff;stroke:currentColor;stroke-width:1}</style>';
 }
 public static function chart($points,$key){
  $values=array_filter(array_column($points,$key),function($v){return $v!==NULL;});if(!$values)return '<p class="ipsMessage ipsMessage--info">Brak danych do wykresu w wybranym okresie.</p>';
  $max=max(1,max($values));$svg='<svg class="sps-stats-chart" viewBox="0 0 900 250" role="img" aria-label="'.($key==='rank'?'Dzienna pozycja według podbić':($key==='views'?'Dzienne wyświetlenia tematu':'Dzienne podbicia')).'"><line x1="45" y1="210" x2="865" y2="210" stroke="currentColor" opacity=".3"/>';$line='';$dots='';$flush=function()use(&$svg,&$line){if($line!=='')$svg.='<polyline points="'.$line.'" fill="none" stroke="#929aff" stroke-width="2"/>';$line='';};
  foreach($points as $i=>$p){if($p[$key]===NULL){$flush();continue;}$x=45+820*$i/max(1,count($points)-1);$v=$p[$key];$y=$key==='rank'?30+170*($v-1)/max(1,$max-1):210-180*$v/$max;$line.=$x.','.$y.' ';$dots.='<circle tabindex="0" cx="'.$x.'" cy="'.$y.'" r="3"><title>'.$p['date'].' - '.$v.'</title></circle>';}$flush();
  return $svg.$dots.'<text x="45" y="238">'.$points[0]['date'].'</text><text x="865" y="238" text-anchor="end">'.$points[count($points)-1]['date'].'</text><text x="10" y="32">'.($key==='rank'?1:$max).'</text><text x="10" y="210">'.($key==='rank'?$max:0).'</text></svg>';
 }
}
