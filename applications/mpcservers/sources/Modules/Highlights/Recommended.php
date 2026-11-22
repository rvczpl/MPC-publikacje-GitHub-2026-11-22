<?php
namespace IPS\mpcservers\Modules\Highlights;

use IPS\mpcservers\Modules\Core\Presentation;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Recommended
{
 public static function allowed(){
  return \IPS\mpcservers\Modules\Core\Registry::enabled('highlights') && \IPS\mpcservers\Modules\Core\Registry::can('highlights');
 }
 public static function canManage($topic){
  return static::allowed() && $topic && \IPS\mpcservers\Modules\Core\Registry::gameFeature(Presentation::forumConfig($topic->forum_id)['category'],'recommended') && Presentation::visible($topic,Presentation::get($topic->tid)?:array());
 }
 public static function badge(){return '<span class="sps-mpc-recommends" data-ipsTooltip title="Polecany serwer przez MPCforum" aria-label="Polecany serwer przez MPCforum" role="img" tabindex="0" style="display:inline-block;color:#d5ac48;margin:0 8px;font-size:1.25em;vertical-align:middle"><i class="fa-solid fa-star" aria-hidden="true"></i></span>';}
 public static function titleTag($topic){
  $row=Presentation::get($topic->tid);
  if(!$row || empty($row['featured']) || !Presentation::visible($topic,$row))return '';
  return '<span class="ipsBadge ipsBadge--style4 sps-recommended-title" title="Polecany serwer przez MPCforum" style="display:inline-block;background:#d5ac48;color:#211b0d;font-size:12px;font-weight:700;line-height:1.5;height:auto;padding:2px 7px;vertical-align:middle;margin-right:8px;white-space:nowrap">MPC Poleca</span> ';
 }
 public static function url($topic){return Presentation::url(array('do'=>'recommend','id'=>(int)$topic->tid));}
 public static function set($topic,$on){
  if(!static::canManage($topic))throw new \DomainException('Nie masz uprawnień do polecania serwerów.');
  $db=\IPS\Db::i();$db->query('START TRANSACTION');
  try{
   $row=$db->query('SELECT featured,is_server FROM `'.$db->prefix.'mpcservers_presentations` WHERE tid='.(int)$topic->tid.' FOR UPDATE')->fetch_assoc();
   if(!$row||!$row['is_server'])throw new \DomainException('To nie jest publikacja serwera.');
   $value=(int)(bool)$on;
   $db->update('mpcservers_presentations',array('featured'=>$value),array('tid=?',(int)$topic->tid));
   if((int)$row['featured']!==$value)$db->insert('mpcservers_crew_audit',array('tid'=>(int)$topic->tid,'actor'=>(int)\IPS\Member::loggedIn()->member_id,'created_at'=>time(),'before_data'=>json_encode(array('action'=>'recommend','featured'=>(int)$row['featured'])),'after_data'=>json_encode(array('action'=>'recommend','featured'=>$value))));
   $db->query('COMMIT');
  }catch(\Throwable $e){$db->query('ROLLBACK');throw $e;}
 }
}
