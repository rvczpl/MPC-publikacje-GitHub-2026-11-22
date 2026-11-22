<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class SystemMigration
{
 public static function run(){
  \IPS\mpcservers\Modules\Metin2\ProxySecret::key();
  $db=\IPS\Db::i();$schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
  foreach($schema as $name=>$definition){if(!$db->checkForTable($name))$db->createTable($definition);}
  foreach(array('mpcservers_crew'=>array('owner_lookup'=>array('tid','role','ended_at','person_id')),'mpcservers_people'=>array('member_lookup'=>array('member_id','merged_into')),'mpcservers_boost_events'=>array('history_lookup'=>array('created','game','tid'))) as $table=>$indexes){foreach($indexes as $name=>$columns){if(!$db->checkForIndex($table,$name))$db->addIndex($table,array('type'=>'key','name'=>$name,'columns'=>$columns,'length'=>array_fill(0,count($columns),NULL)));}}
  if(!(int)\IPS\Settings::i()->mpcs_modular_migration){
   $rewards=json_decode((string)\IPS\Settings::i()->sps_game_rewards,TRUE)?:array();foreach(Presentation::categories() as $game=>$label)if(GameRewards::supports($game)&&!array_key_exists($game,$rewards))$rewards[$game]=(bool)\IPS\Settings::i()->sps_rewards_enabled;
   \IPS\Settings::i()->changeValues(array('sps_game_rewards'=>json_encode($rewards),'mpcs_modular_migration'=>40000));
  }
  if(!(int)\IPS\Settings::i()->sps_views_started)\IPS\Settings::i()->changeValues(array('sps_views_started'=>time()));
 }
}
