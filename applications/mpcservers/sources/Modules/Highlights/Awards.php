<?php
namespace IPS\mpcservers\Modules\Highlights;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Awards
{
 protected static $badges=array();
 public static function install(){
  $db=\IPS\Db::i();$schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
  foreach(array('game','mode_data') as $column)if(!$db->checkForColumn('mpcservers_boost_events',$column))$db->addColumn('mpcservers_boost_events',$schema['mpcservers_boost_events']['columns'][$column]);
  if(!$db->checkForIndex('mpcservers_boost_events','member_day'))$db->addIndex('mpcservers_boost_events',$schema['mpcservers_boost_events']['indexes']['member_day']);
  foreach(array('mpcservers_awards','mpcservers_award_periods') as $table)if(!$db->checkForTable($table))$db->createTable($schema[$table]);
  if(!(int)\IPS\Settings::i()->sps_awards_started)\IPS\Settings::i()->changeValues(array('sps_awards_started'=>time()));
 }
 public static function modesFor($topic){
  $out=array();$cfg=Servers::enabledForum($topic->forum_id);$attrs=Servers::attrRow($topic->tid,$topic->forum_id);
  foreach(($attrs['attrs']??array()) as $group=>$values)foreach((array)$values as $value){if(isset($cfg['groups'][$group]['options'][$value]))$out[$group.':'.$value]=$cfg['groups'][$group]['options'][$value];}
  return $out;
 }
 public static function options($game){
  $out=array();if($game==='')return $out;foreach(Presentation::forumIds() as $id){if(Presentation::forumConfig($id)['category']!==$game)continue;$cfg=Servers::enabledForum($id);foreach(($cfg['groups']??array()) as $group=>$definition)foreach($definition['options'] as $value=>$label)$out[$group.':'.$value]=$label;}
  // Keep archived type names selectable after ACP options change.
  foreach(\IPS\Db::i()->select('mode_data','mpcservers_boost_events',array('game=? AND mode_data IS NOT NULL',$game),NULL,NULL,'mode_data') as $encoded){foreach((array)json_decode($encoded,TRUE) as $key=>$label)if(!isset($out[$key]))$out[$key]=$label;}
  return $out;
 }
 public static function counts($period,$game='',$mode=''){
  $out=array();$where=array(array('created>=? AND created<?',$period['start'],$period['end']));if($game!=='')$where[]=array('game=? OR game=?',$game,'');
  foreach(\IPS\Db::i()->select('tid,game,mode_data,COUNT(*) AS total,MAX(created) AS last_vote','mpcservers_boost_events',$where,NULL,NULL,array('tid','game','mode_data')) as $row){$modes=(array)json_decode($row['mode_data']??'',TRUE);if($game!=='' && $row['game']==='' && (Presentation::get($row['tid'])['category']??'')!==$game)continue;if($mode!=='' && !isset($modes[$mode]))continue;$id=(int)$row['tid'];if(!isset($out[$id]))$out[$id]=array('votes'=>0,'bumped'=>0);$out[$id]['votes']+=(int)$row['total'];$out[$id]['bumped']=max($out[$id]['bumped'],(int)$row['last_vote']);}return $out;
 }
 public static function title($period,$game,$modeLabel){
  $tz=new \DateTimeZone('Europe/Warsaw');$d=new \DateTimeImmutable($period['date'],$tz);$label=array('day'=>'Serwer dnia','week'=>'Serwer tygodnia','month'=>'Serwer miesiąca','year'=>'Serwer roku')[$period['kind']];
  $stamp=$period['kind']==='year'?$d->format('Y'):($period['kind']==='month'?$d->format('m.Y'):$d->format('d.m.Y'));
  $cats=Presentation::categories();return $label.' '.$stamp.($game!==''?' - '.($cats[$game]??$game):'').($modeLabel!==''?' - '.$modeLabel:'');
 }
 public static function settle($now=NULL){
  if(!\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('awards'))return;
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('highlights'))return;
  $now=$now??time();$started=(int)\IPS\Settings::i()->sps_awards_started;if(!$started)return;
  foreach(array_keys(Rankings::periods()) as $kind){
   $initial=Rankings::period($kind,'',$started);$cursor=$initial['start']===$started?$started:$initial['end'];
   $last=\IPS\Db::i()->select('MAX(ends)','mpcservers_award_periods',array('period=?',$kind))->first();if($last)$cursor=max($cursor,(int)$last);
   for($i=0;$i<3;$i++){$period=Rankings::period($kind,'',$cursor);if($period['end']>$now)break;static::closePeriod($period,$now);$cursor=$period['end'];}
  }
 }
 public static function closePeriod($period,$now=NULL){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('highlights'))return;
  $now=$now??time();$started=(int)\IPS\Settings::i()->sps_awards_started;if(!$started || $period['start']<$started || $period['end']>$now)return;
  $db=\IPS\Db::i();$db->query('START TRANSACTION');
  try{
   $claimed=$db->insert('mpcservers_award_periods',array('period'=>$period['kind'],'starts'=>$period['start'],'ends'=>$period['end']),FALSE,TRUE);
   if(!$claimed){$db->query('ROLLBACK');return;}
   $eligible=array();$ids=Presentation::forumIds();$directory=Presentation::directoryIds();
   foreach($db->select('p.tid',array('mpcservers_presentations','p'),array(array('p.is_server=1 AND p.featured=0 AND t.approved=1 AND t.state<>?','link'),array($db->in('t.forum_id',$ids?:array(0)))))->join(array('forums_topics','t'),'t.tid=p.tid','INNER') as $tid){if(\IPS\mpcservers\Modules\Core\Registry::gameFeature(Presentation::get($tid)['category']??'','top')&&!in_array((int)$tid,array_map('intval',$directory),TRUE))$eligible[(int)$tid]=TRUE;}
   $scores=array();$contexts=array();
   foreach($db->select('tid,game,mode_data,COUNT(*) AS total','mpcservers_boost_events',array('created>=? AND created<?',$period['start'],$period['end']),NULL,NULL,array('tid','game','mode_data')) as $r){$tid=(int)$r['tid'];if(!isset($eligible[$tid]))continue;$modes=(array)json_decode($r['mode_data']??'',TRUE);$targets=array(array('','', ''));if($r['game']!=='')$targets[]=array($r['game'],'','');
    foreach($modes as $key=>$label)$targets[]=array($r['game'],$key,$label);
    foreach($targets as $ctx){list($game,$mode,$label)=$ctx;if($game==='' && $mode!=='')continue;$key=json_encode(array($game,$mode));$contexts[$key]=$ctx;$scores[$key][$tid]=($scores[$key][$tid]??0)+(int)$r['total'];}
   }
   foreach($scores as $key=>$totals){$best=max($totals);list($game,$mode,$label)=$contexts[$key];foreach($totals as $tid=>$points){if($points!==$best)continue;$db->insert('mpcservers_awards',array('tid'=>$tid,'period'=>$period['kind'],'starts'=>$period['start'],'ends'=>$period['end'],'game'=>$game,'mode_key'=>$mode,'mode_label'=>$label,'points'=>$points,'title'=>mb_substr(static::title($period,$game,$label),0,255)),FALSE,TRUE);}}
   $db->query('COMMIT');static::$badges=array();
  }catch(\Throwable $e){$db->query('ROLLBACK');throw $e;}
 }
 public static function badges($tid){
  if(!\IPS\mpcservers\Modules\Core\Registry::gameFeature(Presentation::get($tid)['category']??'','top'))return '';
  if(!empty(Presentation::get($tid)['featured']))return '';
  if(!isset(static::$badges[$tid])){ $out='';foreach(\IPS\Db::i()->select('*','mpcservers_awards',array('tid=?',(int)$tid),'ends DESC,id DESC',12) as $r)$out.='<span class="ipsBadge ipsBadge--style4" style="white-space:normal;line-height:1.5;height:auto;margin:3px 5px 3px 0" title="Podbicia: '.(int)$r['points'].'">★ '.Directory::esc($r['title']).'</span>';static::$badges[$tid]=$out;}
  return static::$badges[$tid];
 }
}
