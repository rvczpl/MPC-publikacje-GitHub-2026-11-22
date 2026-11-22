<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Community\CrewProfile;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class CrewRating
{
 public static function allowed($person){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community'))return FALSE;
  $member=\IPS\Member::loggedIn();if(!$member->member_id||(int)$person['member_id']===(int)$member->member_id)return FALSE;
  $records=Crew::records($person['id']);if(!$records)return FALSE;
  if((string)\IPS\Settings::i()->sps_crew_rating_access!=='team')return TRUE;
  // An overlapping collaboration, not merely working on the same server years apart.
  $ownRecords=CrewProfile::records($member);
  foreach($records as $target){foreach($ownRecords as $own){
   if((int)$own['member_id']!==(int)$member->member_id||(int)$own['tid']!==(int)$target['tid'])continue;
   $a=$own['since_date']?strtotime($own['since_date']):(int)$own['created_at'];$b=$target['since_date']?strtotime($target['since_date']):(int)$target['created_at'];
   if(max($a,$b)<=min($own['ended_at']?:time(),$target['ended_at']?:time()))return TRUE;
  }}return FALSE;
 }
 public static function summary($id){
  $r=\IPS\Db::i()->select('COUNT(*) AS total,AVG(score) AS average','mpcservers_crew_ratings',array('person_id=?',(int)$id))->first();return array('count'=>(int)$r['total'],'average'=>(float)$r['average']);
 }
 public static function html($id){
  $r=static::summary($id);return $r['count']?'<span class="sps-person-rating"><i class="fa-solid fa-star" aria-hidden="true"></i> '.number_format($r['average'],2,',','').' / 5 <small>('.$r['count'].' ocen)</small></span>':'<span class="i-color_soft">Brak ocen</span>';
 }
 public static function save($id,$score){
  $p=Crew::person($id);if(!static::allowed($p))throw new \DomainException('Nie masz uprawnień do oceniania tej osoby.');
  if(!is_numeric($score)||(int)$score!=$score||$score<1||$score>5)throw new \DomainException('Wybierz ocenę od 1 do 5.');
  \IPS\Db::i()->replace('mpcservers_crew_ratings',array('person_id'=>(int)$p['id'],'member_id'=>(int)\IPS\Member::loggedIn()->member_id,'score'=>(int)$score,'updated'=>time()));
 }
 public static function link($id){return '<a class="ipsButton ipsButton--tiny ipsButton--inherit" href="'.Directory::esc(Crew::url(array('do'=>'rate','id'=>(int)$id))).'">Oceń osobę</a>';}
}
