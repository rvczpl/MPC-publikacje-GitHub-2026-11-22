<?php
namespace IPS\mpcservers\setup\upg_60008;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade
{
 public function step1(): bool
 {
  $db=\IPS\Db::i();if(!$db->checkForTable('mpcservers_tag_backup')){$schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);$db->createTable($schema['mpcservers_tag_backup']);}
  \IPS\mpcservers\Modules\Core\PublicationTags::enforceForums();return TRUE;
 }
 public function step2(): bool|array
 {
  $result=\IPS\mpcservers\Modules\Core\PublicationTags::batch((int)($_SESSION['mpcs_tags_608']??0),50);
  if($result['done']){unset($_SESSION['mpcs_tags_608']);return TRUE;}
  $_SESSION['mpcs_tags_608']=$result['last'];return array('last'=>$result['last']);
 }
 public function step2CustomTitle(): string { return 'Zapisywanie prefiksów publikacji jako tagów IPS'; }
}
