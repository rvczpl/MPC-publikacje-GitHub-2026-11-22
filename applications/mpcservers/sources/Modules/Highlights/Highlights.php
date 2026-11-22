<?php
namespace IPS\mpcservers\Modules\Highlights;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Highlights\Awards;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Highlights
{
 public static function labels(){return array('day'=>'Serwer dnia','week'=>'Serwer tygodnia','month'=>'Serwer miesiąca','year'=>'Serwer roku');}
 public static function config($game){
  $all=json_decode((string)\IPS\Settings::i()->sps_highlights,TRUE)?:array();$out=array();
  foreach(static::labels() as $kind=>$label){$out[$kind]=$all[$game][$kind]??array('enabled'=>TRUE,'modes'=>array('__game'));}return $out;
 }
 public static function fieldIds($game){$ids=array('sps_hl_forums_'.$game);foreach(static::labels() as $kind=>$label){$ids[]='sps_hl_'.$game.'_'.$kind;$ids[]='sps_hl_modes_'.$game.'_'.$kind;}return $ids;}
 /* Forums (działy) assigned to a game in the publication settings. */
 public static function gameForums($game){
  $out=array();foreach(Presentation::forumIds() as $id){if((Presentation::forumConfig($id)['category']??'')!==$game)continue;try{$out[(int)$id]=\IPS\forums\Forum::load((int)$id)->_title;}catch(\OutOfRangeException $e){}}return $out;
 }
 /* Empty list = winners are shown in every forum of the game. */
 public static function displayForums($game){$all=json_decode((string)\IPS\Settings::i()->sps_highlights,TRUE)?:array();return array_values(array_map('intval',(array)($all[$game]['_forums']??array())));}
 public static function fields($form,$game,$label){
  $cfg=static::config($game);$options=array('__game'=>'Cała gra (wszystkie tryby)')+Awards::options($game);$forums=static::gameForums($game);
  $key='sps_hl_forums_'.$game;$selected=array_values(array_intersect(static::displayForums($game),array_keys($forums)));
  $f=new \IPS\Helpers\Form\Select($key,$selected?:'all',FALSE,array('options'=>$forums,'multiple'=>TRUE,'unlimited'=>'all','parse'=>'normal'),NULL,NULL,NULL,$key);$f->label='Działy z wyróżnieniami';$f->description='Działy publikacji tej gry, w których nad listą tematów pokazujemy aktualnych zwycięzców. Wyróżnienia zawsze są widoczne na liście serwerów i w zakładce TOP serwery.';$form->add($f);
  foreach(static::labels() as $kind=>$title){
   $modesKey='sps_hl_modes_'.$game.'_'.$kind;$key='sps_hl_'.$game.'_'.$kind;
   $f=new \IPS\Helpers\Form\YesNo($key,!empty($cfg[$kind]['enabled']),FALSE,array('togglesOn'=>array($modesKey)),NULL,NULL,NULL,$key);$f->label=$title;$f->description='Zwycięzca ostatniego zakończonego okresu jest pokazywany przez cały kolejny taki okres.';$form->add($f);
   $f=new \IPS\Helpers\Form\Select($modesKey,(array)$cfg[$kind]['modes'],FALSE,array('options'=>$options,'multiple'=>TRUE,'parse'=>'normal'),NULL,NULL,NULL,$modesKey);$f->label=$title.' - rankingi (prefiksy trybów)';$f->description='Ranking całej gry lub osobny ranking dla prefiksu trybu. Przy remisie wyróżniamy wszystkich zwycięzców. Pusty wybór nie wyświetla wyróżnień.';$form->add($f);
  }
 }
 public static function save($game,$values){
  $all=json_decode((string)\IPS\Settings::i()->sps_highlights,TRUE)?:array();$options=array('__game'=>'')+Awards::options($game);
  foreach(static::labels() as $kind=>$label){if(!array_key_exists('sps_hl_'.$game.'_'.$kind,$values))continue;$modes=array();foreach((array)($values['sps_hl_modes_'.$game.'_'.$kind]??array()) as $mode){if(is_scalar($mode)&&isset($options[$mode]))$modes[]=$mode;}$all[$game][$kind]=array('enabled'=>!empty($values['sps_hl_'.$game.'_'.$kind]),'modes'=>array_values(array_unique($modes)));}
  if(array_key_exists('sps_hl_forums_'.$game,$values)){$forums=static::gameForums($game);$chosen=$values['sps_hl_forums_'.$game];$all[$game]['_forums']=$chosen==='all'?array():array_values(array_filter(array_map('intval',(array)$chosen),function($id)use($forums){return isset($forums[$id]);}));}
  \IPS\Settings::i()->changeValues(array('sps_highlights'=>json_encode($all,JSON_UNESCAPED_UNICODE)));
 }
 public static function winners($game,$now=NULL){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('highlights')||($game!==''&&(!\IPS\mpcservers\Modules\Core\Registry::gameFeature($game,'top')||!isset(Presentation::categories()[$game]))))return array();$now=$now??time();$cfg=static::config($game);$out=array();
  foreach(static::labels() as $kind=>$label){
   if(empty($cfg[$kind]['enabled'])||empty($cfg[$kind]['modes']))continue;
   $current=Rankings::period($kind,'',$now);$previous=Rankings::period($kind,'',$current['start']-1);
   $modes=array_map(function($m){return $m==='__game'?'':$m;},$cfg[$kind]['modes']);
   $where=array(array('game=? AND period=? AND starts=? AND ends=?',$game,$kind,$previous['start'],$previous['end']),array_merge(array('mode_key IN ('.implode(',',array_fill(0,count($modes),'?')).')'),$modes));
   foreach(\IPS\Db::i()->select('*','mpcservers_awards',$where,'mode_key ASC,tid ASC') as $award){
    $topic=Presentation::topic($award['tid']);$meta=Presentation::get($award['tid']);
    if(!$topic||!$meta||!empty($meta['featured'])||!Presentation::visible($topic,$meta)||($game!==''&&$meta['category']!==$game)||!\IPS\mpcservers\Modules\Core\Registry::gameFeature($meta['category'],'top'))continue;
    $id=(int)$topic->tid;if(!isset($out[$id]))$out[$id]=array('topic'=>$topic,'meta'=>$meta,'awards'=>array());
    $award['expires']=$current['end'];$out[$id]['awards'][]=$award;
   }
  }return $out;
 }
 public static function cards($winners){
  if(!$winners)return '';$e=array('IPS\\mpcservers\\Directory','esc');$out='<section class="sps-winners" aria-label="Wyróżnione serwery"><h2 class="ipsTitle ipsTitle--h3">Wyróżnione serwery</h2><div class="sps-winner-grid">';
  foreach($winners as $entry){$t=$entry['topic'];$out.='<article class="sps-winner"><a class="sps-winner-title" href="'.$e($t->url()).'">'.Presentation::iconHtml($entry['meta']).'<strong>'.$e($t->title).'</strong></a><ul class="">';
   foreach($entry['awards'] as $a){$out.='<li><span class="sps-winner-award">'.$e($a['title']).'</span><small>'.$e((int)$a['points']).' podbić · wyróżnienie do '.$e((new \DateTimeImmutable('@'.($a['expires']-1)))->setTimezone(new \DateTimeZone('Europe/Warsaw'))->format('d.m.Y H:i')).'</small></li>';}
   $out.='</ul></article>';
  }return $out.'</div></section>'.static::css();
 }
 public static function forum($forum){
  if(!Presentation::enabled($forum->_id)||!$forum->can('view')||\IPS\Request::i()->advancedSearchForm)return '';
  $game=Presentation::forumConfig($forum->_id)['category'];$only=static::displayForums($game);if($only&&!in_array((int)$forum->_id,$only,TRUE))return '';
  $winners=static::winners($game);if(!$winners)return '';
  // Only emitted from the forum header when visible winners exist; normal fallback needs no JS.
  return static::cards($winners).'<style>#ipsLayout_mainArea .ipsPageHeader>header>.ipsRichText,#ipsLayout_mainArea .cForumList,#ipsLayout_mainArea #subforums_list{display:none!important}</style>';
 }
 public static function css(){return '<style>.sps-winners{margin:18px 0;text-align:left;min-width:0}.sps-winner-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,250px),1fr));gap:14px;margin-top:12px}.sps-winner{padding:18px;border:1px solid rgba(153,122,235,.65);border-radius:10px;background:linear-gradient(135deg,rgba(153,122,235,.18),rgba(111,98,220,.08));min-width:0}.sps-winner-title{display:flex;align-items:center;gap:12px;overflow-wrap:anywhere;font-size:1.1em}.sps-winner-title strong{min-width:0}.sps-winner-title img{margin:0!important;float:none!important;width:48px;height:48px;object-fit:cover;border-radius:8px;flex-shrink:0}.sps-winner li{margin-top:10px}.sps-winner-award{display:block;color:#aa8be8;font-weight:700;overflow-wrap:anywhere}.sps-winner small{display:block;margin-top:4px;opacity:.8}.sps-current-winner td{background:rgba(153,122,235,.13)!important}.sps-current-winner td:first-child{box-shadow:inset 3px 0 #997aeb}.sps-winner-podium>td{padding:0!important}.sps-winner-podium>td:before{display:none!important}</style>';}
}
