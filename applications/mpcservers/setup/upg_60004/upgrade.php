<?php
namespace IPS\mpcservers\setup\upg_60004;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade
{
 public function step1(): bool
 {
  $db=\IPS\Db::i();
  if(!$db->checkForColumn('mpcservers_presentations','mc_versions')){
   $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
   $db->addColumn('mpcservers_presentations',$schema['mpcservers_presentations']['columns']['mc_versions']);
  }
  return TRUE;
 }
 public function step1CustomTitle(): string { return 'Dodawanie wyboru wersji Minecraft bez zmiany istniejących publikacji'; }
}
