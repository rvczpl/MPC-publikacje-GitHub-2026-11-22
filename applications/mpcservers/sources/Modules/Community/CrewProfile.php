<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Community\CrewDirectory;
use IPS\mpcservers\Modules\Community\CrewRating;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class CrewProfile
{
 public static function records($member){
  if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community'))return array();
  $out=array();if(!$member->member_id)return $out;
  foreach(\IPS\Db::i()->select('id','mpcservers_people',array('member_id=? AND merged_into=0',(int)$member->member_id)) as $id)$out=array_merge($out,Crew::records($id));return $out;
 }
 public static function render($member){
  $records=static::records($member);if(!$records)return '<p>Brak dostępnych powiązań z serwerami.</p>';
  $e=array('IPS\\mpcservers\\Directory','esc');$servers=array();$persons=array();$active=array();$roles=array();
  foreach($records as $r){$id=(int)$r['tid'];$persons[$r['person_id']]=$r['name'];$roles[$r['role_label']]=TRUE;if(!isset($servers[$id]))$servers[$id]=array('topic'=>$r['topic'],'roles'=>array(),'active'=>FALSE);$servers[$id]['roles'][$r['role_label']]=TRUE;if(!$r['ended_at']){$active[$id]=TRUE;$servers[$id]['active']=TRUE;}}
  $totals=array();foreach(\IPS\Db::i()->select('tid,COUNT(*) AS n','mpcservers_boost_events',array(\IPS\Db::i()->in('tid',array_keys($servers))),NULL,NULL,'tid') as $r)$totals[$r['tid']]=(int)$r['n'];
  $weekly=Rankings::counts(Rankings::period());$week=0;foreach($servers as $id=>$r)$week+=$weekly[$id]['votes']??0;
  $out='<section class="sps-directory i-padding_3"><header class="sps-list-heading"><h2>Serwery i ekipy</h2>'.Directory::menu('','people').'</header><p>'.$e(implode(', ',array_keys($roles))).'</p><div class="sps-profile-counts">';
  foreach(array('Serwery obecnie'=>count($active),'Serwery w historii'=>count($servers),'Podbicia w tygodniu'=>$week,'Podbicia w zapisanej historii'=>array_sum($totals)) as $label=>$n)$out.='<div><strong>'.$n.'</strong><span>'.$label.'</span></div>';
  $out.='</div><p class="i-color_soft">Statystyki dotyczą dostępnych publikacji i zapisanej historii. Podbicia należą do serwerów - nie są punktami tej osoby i nie są ograniczone do czasu jej współpracy.</p>';
  foreach($persons as $pid=>$name){$out.='<div class="sps-profile-rating"><strong>'.$e($name).'</strong> '.CrewRating::html($pid);if(CrewRating::allowed(Crew::person($pid)))$out.=' '.CrewRating::link($pid);$out.='</div>';}
  $out.='<div class="sps-table-wrap"><table class="ipsTable sps-server-table"><thead><tr><th>Serwer</th><th>Role</th><th>Współpraca</th><th>Podbicia w tygodniu</th><th>Podbicia w historii</th></tr></thead><tbody>';
  foreach($servers as $id=>$r){$out.='<tr><td class="sps-server-body" data-label="Serwer"><a href="'.$e($r['topic']->url()).'">'.$e($r['topic']->title).'</a></td><td data-label="Role">'.$e(implode(', ',array_keys($r['roles']))).'</td><td data-label="Współpraca">'.($r['active']?'Obecna':'Zakończona').'</td><td data-label="Podbicia w tygodniu">'.($weekly[$id]['votes']??0).'</td><td data-label="Podbicia w historii">'.($totals[$id]??0).'</td></tr>';}
  return $out.'</tbody></table></div>'.CrewDirectory::graph($records).'</section>'.Directory::assets().CrewDirectory::assets().'<style>.sps-profile-counts{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:20px 0}.sps-profile-counts>div{border:1px solid #8884;border-radius:10px;padding:16px}.sps-profile-counts strong{display:block;font-size:26px}.sps-profile-counts span{display:block;margin-top:6px}.sps-profile-rating{display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin:16px 0}.sps-person-rating .fa{color:#d5ac48}</style>';
 }
}
