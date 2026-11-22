<?php
namespace IPS\mpcservers\Modules\Minecraft;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\ModuleAccess;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Reward capability follows the configured display name, not a hardcoded game key. */
class GameRewards
{
 public static function supports($game){return \IPS\mpcservers\Modules\Core\Registry::kind($game)==='minecraft' && \IPS\mpcservers\Modules\Core\Registry::enabled('minecraft');}

 public static function config($game){$all=json_decode((string)\IPS\Settings::i()->sps_game_rewards,TRUE)?:array();$v=$all[$game]??FALSE;return is_array($v)?array('enabled'=>!empty($v['enabled']),'exceptions'=>array_values(array_filter(array_map('intval',(array)($v['exceptions']??array()))))):array('enabled'=>(bool)$v,'exceptions'=>array());}
 public static function enabled($game,$tid=0){$c=static::config($game);return static::supports($game)&&($c['enabled']||((int)$tid>0&&in_array((int)$tid,$c['exceptions'],TRUE)));}
 public static function choices($game){
  $out=array();foreach(\IPS\Db::i()->select('p.tid,t.title',array('mpcservers_presentations','p'),array('p.category=? AND p.is_server=1',$game),'t.title ASC')->join(array('forums_topics','t'),'t.tid=p.tid','INNER') as $r)$out[(int)$r['tid']]=$r['title'].' (#'.(int)$r['tid'].')';return $out;
 }
 public static function field($form,$game){
  if(!static::supports($game)||!ModuleAccess::can('minecraft'))return;
  $c=static::config($game);$key='sps_rewards_game_'.$game;
  $f=new \IPS\Helpers\Form\YesNo($key,$c['enabled'],FALSE);$f->label='Nagrody za podbijanie - '.Presentation::categories()[$game];$f->description='Włącz nagrody dla wszystkich serwerów tej gry. Po wyłączeniu nagrody działają tylko dla wyjątków wybranych poniżej. Podbijanie nadal działa dla wszystkich.';$form->add($f);
  $choices=static::choices($game);$f=new \IPS\Helpers\Form\Select('sps_rewards_exceptions_'.$game,array_values(array_intersect($c['exceptions'],array_keys($choices))),FALSE,array('options'=>$choices,'multiple'=>TRUE),function($v)use($choices){foreach((array)$v as $id)if(!is_scalar($id)||!ctype_digit((string)$id)||!isset($choices[(int)$id]))throw new \DomainException('Wybierz serwer z listy tej gry.');});
  $f->label='Wyjątki - serwery z nagrodami';$f->description='Te serwery mogą korzystać z nagród również przy wyłączonym przełączniku powyżej. Usunięcie wyjątku blokuje także odbiór wcześniej wydanych kodów. Tokeny i historia pozostają zapisane.';$form->add($f);
 }
 public static function save($game,$values){
  if(!ModuleAccess::can('minecraft')||!static::supports($game)||!array_key_exists('sps_rewards_game_'.$game,$values))return;
  $ids=array_values(array_unique(array_map('intval',(array)($values['sps_rewards_exceptions_'.$game]??array()))));if(array_diff($ids,array_keys(static::choices($game))))throw new \DomainException('Wyjątek musi dotyczyć serwera tej gry.');
  $all=json_decode((string)\IPS\Settings::i()->sps_game_rewards,TRUE)?:array();$all[$game]=array('enabled'=>(bool)$values['sps_rewards_game_'.$game],'exceptions'=>$ids);\IPS\Settings::i()->changeValues(array('sps_game_rewards'=>json_encode($all)));
 }
}
