<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Community\OwnerStats;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class TopicViews
{
 protected static $recorded=array();
 public static function record($topic){
  if(!\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('topic_views'))return;
  if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||\IPS\Request::i()->isAjax()||isset(static::$recorded[$topic->tid]))return;
  $meta=Presentation::get($topic->tid);if(!$meta||!Presentation::visible($topic,$meta))return;
  static::$recorded[$topic->tid]=TRUE;
  $day=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Warsaw')))->format('Y-m-d');
  \IPS\Db::i()->query('INSERT INTO `'.\IPS\Db::i()->prefix.'mpcservers_topic_views` (tid,view_day,views) VALUES ('.(int)$topic->tid.',\''.$day.'\',1) ON DUPLICATE KEY UPDATE views=views+1');
 }
 public static function section($tid,$starts,$options,$pick,$today){
  $since=(int)\IPS\Settings::i()->sps_views_started;$zone=new \DateTimeZone('Europe/Warsaw');$daily=array();$counts=array_fill_keys(array_keys($options),0);
  foreach(\IPS\Db::i()->select('*','mpcservers_topic_views',array('tid=? AND view_day>=?',(int)$tid,(new \DateTimeImmutable('@'.min($starts)))->setTimezone($zone)->format('Y-m-d'))) as $r){$daily[$r['view_day']]=(int)$r['views'];$timestamp=(new \DateTimeImmutable($r['view_day'],$zone))->getTimestamp();foreach($starts as $key=>$start)if($timestamp>=$start)$counts[$key]+=(int)$r['views'];}
  $out='<h2>Wyświetlenia tematu</h2><p>Liczymy otwarcia stron tematu, również ponowne wejścia tej samej osoby. To nie jest liczba unikalnych użytkowników. Historia od '.Directory::esc((new \DateTimeImmutable('@'.$since))->setTimezone($zone)->format('d.m.Y H:i')).'. Wcześniejszych wyświetleń nie rozdzielamy na dni.</p><div class="sps-stats-counters">';
  foreach($options as $key=>$label)$out.='<a href="'.Directory::esc(OwnerStats::url($tid)->setQueryString('range',$key)).'"'.($pick===(string)$key?' aria-current="page"':'').'><span>'.$label.'</span><strong>'.$counts[$key].'</strong><small>'.($starts[$key]>=$since?'wyświetleń':'niepełna historia').'</small></a>';
  $points=array();$sinceDay=(new \DateTimeImmutable('@'.$since))->setTimezone($zone)->format('Y-m-d');
  for($at=(new \DateTimeImmutable('@'.$starts[$pick]))->setTimezone($zone);$at<=$today;$at=$at->modify('+1 day')){$day=$at->format('Y-m-d');$points[]=array('date'=>$at->format('d.m.Y'),'views'=>$day>=$sinceDay?($daily[$day]??0):NULL);}
  $out.='</div><h3>Wyświetlenia dziennie - '.$options[$pick].'</h3>'.OwnerStats::chart($points,'views').'<p class="i-color_soft">Pierwszy dzień zbierania danych jest niepełny. Dni sprzed uruchomienia historii oznaczamy jako brak danych.</p><details><summary>Wyświetlenia - dane dzienne</summary><div class="sps-table-wrap"><table class="ipsTable sps-server-table"><thead><tr><th>Data</th><th>Wyświetlenia</th></tr></thead><tbody>';
  foreach(array_reverse($points) as $p)$out.='<tr><td data-label="Data">'.$p['date'].'</td><td data-label="Wyświetlenia">'.($p['views']??'Brak danych').'</td></tr>';
  return $out.'</tbody></table></div></details>';
 }
}
