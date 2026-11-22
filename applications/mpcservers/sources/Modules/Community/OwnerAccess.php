<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Core\Presentation;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Explicit current owner role is the only authority for the private panel. */
class OwnerAccess
{
 public static function isOwner($tid,$memberId){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community')||!(int)$tid||!(int)$memberId)return FALSE;
  return (bool)\IPS\Db::i()->select('COUNT(*)',array('mpcservers_crew','c'),array('c.tid=? AND c.role=? AND c.ended_at=0 AND p.member_id=? AND p.merged_into=0',(int)$tid,'owner',(int)$memberId))->join(array('mpcservers_people','p'),'p.id=c.person_id','INNER')->first();
 }
 public static function ids($memberId){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community')||!(int)$memberId)return array();
  return iterator_to_array(\IPS\Db::i()->select('DISTINCT c.tid',array('mpcservers_crew','c'),array('c.role=? AND c.ended_at=0 AND p.member_id=? AND p.merged_into=0','owner',(int)$memberId))->join(array('mpcservers_people','p'),'p.id=c.person_id','INNER'));
 }
 public static function requireTopic($tid,$member){
  if(!static::isOwner($tid,$member->member_id))throw new \OutOfRangeException;
  $topic=Presentation::topic($tid);if(!$topic||!Presentation::visible($topic,Presentation::get($tid)?:array()))throw new \OutOfRangeException;
  return $topic;
 }
}
