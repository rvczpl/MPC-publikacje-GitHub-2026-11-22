<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Product modules and game policies. Storage IDs deliberately remain unchanged. */
class Registry
{
 public static function definitions(){return array(
  'metin2'=>array('label'=>'Metin2','permission'=>'sls_metin2_manage'),
  'lists'=>array('label'=>'Listy serwerów','permission'=>'sls_lists_manage'),
  'highlights'=>array('label'=>'MPC Poleca i TOP serwery','permission'=>'sls_highlights_manage'),
  'community'=>array('label'=>'Osoby, właściciele i powiązania','permission'=>'sls_community_manage'),
  'minecraft'=>array('label'=>'Minecraft','permission'=>'sls_minecraft_manage')
 );}
 public static function enabled($module){$all=json_decode((string)\IPS\Settings::i()->sls_modules,TRUE)?:array();return isset(static::definitions()[$module])&&($all[$module]??TRUE);}
 public static function can($module){$d=static::definitions()[$module]??NULL;return $d&&\IPS\Member::loggedIn()->member_id&&\IPS\Member::loggedIn()->hasAcpRestriction('mpcservers',$module,$d['permission']);}
 public static function admin($module){if(!static::can($module))\IPS\Output::i()->error('Brak uprawnień do tego modułu.','SLS/ADMIN',403,'');}
 public static function front($module){if(!static::enabled($module))\IPS\Output::i()->error('Ten moduł jest wyłączony.','SLS/MODULE',404,'');}
 public static function kind($game){
  $map=json_decode((string)\IPS\Settings::i()->sls_game_bindings,TRUE)?:array();
  if(isset($map[$game]))return in_array($map[$game],array('metin2','minecraft'),TRUE)?$map[$game]:'legacy';
  $label=mb_strtolower((string)(Presentation::categories()[$game]??$game));
  if(strpos($label,'minecraft')!==FALSE||$game==='minecraft')return 'minecraft';
  if(strpos($label,'metin')!==FALSE||in_array($game,array('metin','metin2'),TRUE))return 'metin2';
  return 'legacy';
 }
 public static function gameEnabled($game){return static::enabled(static::kind($game));}
 public static function games($module){$out=array();foreach(Presentation::categories() as $key=>$label)if(static::kind($key)===$module)$out[$key]=$label;return $out;}
 public static function gameFeature($game,$feature){
  if(!static::enabled('highlights')||!static::gameEnabled($game))return FALSE;
  $settings=json_decode((string)\IPS\Settings::i()->sls_game_features,TRUE)?:array();return $settings[$game][$feature]??TRUE;
 }
 public static function bind($game,$module){if(!in_array($module,array('metin2','minecraft'),TRUE))throw new \DomainException('Nieprawidłowy moduł gry.');$map=json_decode((string)\IPS\Settings::i()->sls_game_bindings,TRUE)?:array();$map[$game]=$module;\IPS\Settings::i()->changeValues(array('sls_game_bindings'=>json_encode($map)));}
 public static function canonicalGame($module){$games=static::games($module);if($games)return (string)key($games);$cats=Presentation::categories();$key=$module==='metin2'?'metin':$module;$cats[$key]=static::definitions()[$module]['label'];$lines=array();foreach($cats as $k=>$v)$lines[]=$k.'='.$v;\IPS\Settings::i()->changeValues(array('sps_categories'=>implode("\n",$lines)));static::bind($key,$module);return $key;}
 public static function migrate(){
  if((int)\IPS\Settings::i()->sls_migrated)return;
  $map=json_decode((string)\IPS\Settings::i()->sls_game_bindings,TRUE)?:array();
  foreach(Presentation::categories() as $game=>$label){
   if(isset($map[$game]))continue;$kind=static::kind($game);
   if($kind==='legacy'){
    $found=array();foreach(Servers::forums(TRUE) as $id=>$cfg){$pc=Presentation::forumConfig($id);if($pc['category']!==$game)continue;$name=mb_strtolower(Servers::forumName($id));if(strpos($name,'metin')!==FALSE)$found['metin2']=TRUE;if(strpos($name,'minecraft')!==FALSE)$found['minecraft']=TRUE;}
    if(count($found)===1)$kind=key($found);
   }
   $map[$game]=$kind;
  }
  \IPS\Settings::i()->changeValues(array('sls_game_bindings'=>json_encode($map),'sls_migrated'=>1));
  static::canonicalGame('metin2');static::canonicalGame('minecraft');
 }
}
