<?php
namespace IPS\mpcservers\Modules\Highlights;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\ModeFilters;
use IPS\mpcservers\Modules\Highlights\Awards;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Rankings
{
 public static function install(){
  $db=\IPS\Db::i();if(!$db->checkForTable('mpcservers_boost_events')){$s=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);$db->createTable($s['mpcservers_boost_events']);}
  if(!(int)\IPS\Settings::i()->sps_rankings_started)\IPS\Settings::i()->changeValues(array('sps_rankings_started'=>time()));
 }
 public static function url($params=array()){return \IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=browse','front')->setQueryString($params);}
 public static function periods(){return array('day'=>'Dzień','week'=>'Tydzień','month'=>'Miesiąc','year'=>'Rok');}
 public static function period($kind='week',$date='', $now=NULL){
  $zone=new \DateTimeZone('Europe/Warsaw');$today=(new \DateTimeImmutable('@'.($now??time())))->setTimezone($zone);
  if(!isset(static::periods()[$kind]))$kind='week';
  $at=Presentation::validDate($date)&&$date!==''?new \DateTimeImmutable($date.' 00:00:00',$zone):$today;
  if($at>$today)$at=$today;$start=$at->setTime(0,0);
  if($kind==='week'){$start=$start->modify('-'.((int)$start->format('N')-1).' days')->modify('-1 minute');if($at>=$start->modify('+1 week'))$start=$start->modify('+1 week');}
  elseif($kind==='month')$start=$start->modify('first day of this month');
  elseif($kind==='year')$start=$start->setDate((int)$start->format('Y'),1,1);
  $unit=array('day'=>'day','week'=>'week','month'=>'month','year'=>'year')[$kind];$end=$start->modify('+1 '.$unit);
  $anchor=$kind==='week'?$start->modify('+1 minute'):$start;
  return array('kind'=>$kind,'start'=>$start->getTimestamp(),'end'=>$end->getTimestamp(),'date'=>$anchor->format('Y-m-d'),'label'=>$kind==='week'?$start->format('d.m.Y H:i').' - '.$end->format('d.m.Y H:i'):$start->format('d.m.Y').' - '.$end->modify('-1 day')->format('d.m.Y'),'previous'=>$anchor->modify('-1 '.$unit)->format('Y-m-d'),'next'=>$anchor->modify('+1 '.$unit)->format('Y-m-d'),'closed'=>$end<=$today);
 }
 public static function counts($period){
  $out=array();foreach(\IPS\Db::i()->select('tid,COUNT(*) AS total,MAX(created) AS last_vote','mpcservers_boost_events',array('created>=? AND created<?',$period['start'],$period['end']),NULL,NULL,'tid') as $row){$out[(int)$row['tid']]=array('votes'=>(int)$row['total'],'bumped'=>(int)$row['last_vote']);}return $out;
 }
 public static function weekly($tid){$period=static::period();return (int)\IPS\Db::i()->select('COUNT(*)','mpcservers_boost_events',array('tid=? AND created>=? AND created<?',(int)$tid,$period['start'],$period['end']))->first();}
 public static function yearly($tid){$period=static::period('year');return (int)\IPS\Db::i()->select('COUNT(*)','mpcservers_boost_events',array('tid=? AND created>=? AND created<?',(int)$tid,$period['start'],$period['end']))->first();}
}
