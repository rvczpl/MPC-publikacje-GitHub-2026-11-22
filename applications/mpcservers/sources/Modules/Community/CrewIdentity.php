<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Community\Crew;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class CrewIdentity
{
 public static function install(){
  if(!\IPS\Db::i()->checkForColumn('mpcservers_people','merged_into')){ $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);\IPS\Db::i()->addColumn('mpcservers_people',$schema['mpcservers_people']['columns']['merged_into']); }
 }
 public static function link($personId,\IPS\Member $member,$merge=FALSE){
  if(!\IPS\mpcservers\ModuleAccess::can('people'))throw new \DomainException('Brak uprawnień do podpinania osób.');
  if(!$member->member_id)throw new \DomainException('Wybierz istniejące konto forum.');
  $db=\IPS\Db::i();$actor=(int)\IPS\Member::loggedIn()->member_id;$now=time();$db->query('START TRANSACTION');
  try{
   $source=Crew::person((int)$personId);
   $db->query('SELECT id FROM `'.$db->prefix.'mpcservers_people` WHERE id='.(int)$source['id'].' FOR UPDATE')->fetch_assoc();
   $source=Crew::person((int)$source['id']);
   if($source['member_id'] && (int)$source['member_id']!==(int)$member->member_id)throw new \DomainException('Ta osoba jest już przypisana do innego konta. Nie można zmienić jej tożsamości.');
   try{$target=$db->select('*','mpcservers_people',array('identity_key=?','member:'.$member->member_id))->first();}catch(\UnderflowException $e){$target=NULL;}
   if($target && (int)$target['id']!==(int)$source['id']){
    if(!$merge)throw new \DomainException('To konto ma już wpis #'.$target['id'].'. Zaznacz potwierdzenie scalenia, jeśli oba wpisy dotyczą tej samej osoby.');
    $db->update('mpcservers_crew',array('person_id'=>(int)$target['id']),array('person_id=?',(int)$source['id']));
    $seen=array();foreach($db->select('*','mpcservers_crew',array('person_id=? AND ended_at=0',(int)$target['id']),'id ASC') as $r){$key=$r['tid'].':'.$r['game'].':'.$r['role'];if(isset($seen[$key])){$db->update('mpcservers_crew',array('ended_at'=>$now,'ended_by'=>$actor),array('id=?',(int)$r['id']));}else{$seen[$key]=TRUE;}}
    $db->update('mpcservers_people',array('merged_into'=>(int)$target['id']),array('id=?',(int)$source['id']));$result=(int)$target['id'];
   }else{
    $db->update('mpcservers_people',array('identity_key'=>'member:'.$member->member_id,'member_id'=>(int)$member->member_id,'name'=>$member->name),array('id=?',(int)$source['id']));$result=(int)$source['id'];
   }
   $db->insert('mpcservers_crew_audit',array('tid'=>0,'actor'=>$actor,'created_at'=>$now,'before_data'=>json_encode(array('action'=>'link_person','source'=>$source,'target'=>$target),JSON_UNESCAPED_UNICODE),'after_data'=>json_encode(array('person_id'=>$result,'member_id'=>(int)$member->member_id,'merged'=>$result!==(int)$source['id']),JSON_UNESCAPED_UNICODE)));
   $db->query('COMMIT');Crew::clearCache();return $result;
  }catch(\Throwable $e){$db->query('ROLLBACK');Crew::clearCache();throw $e;}
 }
}
