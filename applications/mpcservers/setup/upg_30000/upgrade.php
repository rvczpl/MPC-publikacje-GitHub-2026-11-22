<?php
namespace IPS\mpcservers\setup\upg_30000;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade {
 public function step1() {
  $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),true);
  foreach (array('mpcservers_presentations','mpcservers_votes','mpcservers_rewards') as $table) {
   if (!\IPS\Db::i()->checkForTable($table)) { \IPS\Db::i()->createTable($schema[$table]); }
  }
  return TRUE;
 }
}
