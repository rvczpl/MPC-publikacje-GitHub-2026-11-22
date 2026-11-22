<?php
namespace IPS\mpcservers\modules\admin\people;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class identities extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\ModuleAccess::requireAdmin('people');parent::execute();}
 protected function manage(){
  $e=array('IPS\\mpcservers\\Directory','esc');$url=\IPS\Http\Url::internal('app=mpcservers&module=people&controller=identities');\IPS\Output::i()->title='Powiązania osób z kontami';
  $form=new \IPS\Helpers\Form('sps_link_person','save');
  $id=new \IPS\Helpers\Form\Number('sps_person_id',(int)\IPS\Request::i()->person,FALSE,array('min'=>1));$id->required=TRUE;$id->label='ID osoby w rejestrze';$id->description='Numer #ID widoczny w spisie właścicieli i ekip. Historia tej osoby zostanie zachowana.';$form->add($id);
  $member=new \IPS\Helpers\Form\Member('sps_person_member',NULL,TRUE);$member->label='Konto forum';$form->add($member);
  $merge=new \IPS\Helpers\Form\YesNo('sps_person_merge',FALSE);$merge->label='Potwierdzam scalenie wpisów tej samej osoby';$merge->description='Jeśli wybrane konto ma już wpis w rejestrze, scalenie połączy ich historię we wszystkich grach. Zaznacz wyłącznie po potwierdzeniu, że to ta sama osoba. Stare ID pozostanie działające.';$form->add($merge);
  if($v=$form->values()){
   \IPS\Session::i()->csrfCheck();try{$result=\IPS\mpcservers\CrewIdentity::link((int)$v['sps_person_id'],$v['sps_person_member'],(bool)$v['sps_person_merge']);\IPS\Output::i()->redirect($url->setQueryString('linked',$result),'saved');}catch(\DomainException $ex){$form->error=$ex->getMessage();}
  }
  $out='<div class="ipsBox i-padding_3"><p>Podłącz istniejącą osobę spoza forum do konta. Sam zgodny nick nie wystarcza do ustalenia tożsamości. Autorzy publikacji nadal mogą oznaczać ekipę; podpinanie kont i scalanie wpisów wykonujesz tutaj w ACP.</p><a href="'.$e(\IPS\mpcservers\Crew::url()).'" target="_blank" rel="noopener">Otwórz spis osób</a>'.(string)$form.'<h2>Osoby bez konta</h2><table class="ipsTable"><thead><tr><th>ID</th><th>Nick</th><th></th></tr></thead><tbody>';
  $page=max(1,(int)\IPS\Request::i()->page);$rows=\IPS\Db::i()->select('*','mpcservers_people',array('member_id=0 AND merged_into=0'),'name ASC',array(($page-1)*30,31));$count=0;
  foreach($rows as $r){if(++$count>30)break;$out.='<tr><td>#'.(int)$r['id'].'</td><td>'.$e($r['name']).'</td><td><a href="'.$e($url->setQueryString('person',(int)$r['id'])).'">Podepnij konto</a></td></tr>';}
  if(!$count)$out.='<tr><td colspan="3">Brak osób oczekujących na powiązanie.</td></tr>';
  $out.='</tbody></table>';if($page>1)$out.='<a href="'.$e($url->setQueryString('page',$page-1)).'">Poprzednia</a> ';if($count>30)$out.='<a href="'.$e($url->setQueryString('page',$page+1)).'">Następna</a>';
  $out.='<h2>Ostatnie przypisania i scalenia</h2><ul>';
  foreach(\IPS\Db::i()->select('*','mpcservers_crew_audit',array('tid=0'),'id DESC',20) as $audit){$before=json_decode($audit['before_data'],TRUE);$after=json_decode($audit['after_data'],TRUE);if(($before['action']??'')!=='link_person')continue;$out.='<li>'.date('d.m.Y H:i',$audit['created_at']).' - '.$e($before['source']['name']).' (#'.(int)$before['source']['id'].') → osoba #'.(int)$after['person_id'].', konto #'.(int)$after['member_id'].'. Zmienił: '.$e(\IPS\Member::load($audit['actor'])->name).'</li>';}
  \IPS\Output::i()->output=$out.'</ul></div>';
 }
}
