<?php
namespace IPS\mpcservers\modules\front\people;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class browse extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::front('community');parent::execute();}

 protected function memberSearch(){
  \IPS\Output::i()->httpHeaders['Cache-Control']='private, no-store';
  if(!\IPS\Member::loggedIn()->member_id){\IPS\Output::i()->json(array('results'=>array()),403);return;}
  $q=\IPS\Request::i()->q;$q=is_scalar($q)?trim((string)$q):'';$results=array();
  if(mb_strlen($q)>=3 && mb_strlen($q)<=80){
   $q=str_replace(array('\\','%','_'),array('\\\\','\\%','\\_'),$q);
   foreach(\IPS\Db::i()->select('DISTINCT name','core_members',array('name LIKE ?',$q.'%'),'name ASC',10) as $row)$results[]=array('name'=>$row);
  }
  \IPS\Output::i()->json(array('results'=>$results));
 }
 protected function rate(){
  try{$person=\IPS\mpcservers\Crew::person((int)\IPS\Request::i()->id);}catch(\DomainException $e){\IPS\Output::i()->error('Nie znaleziono osoby.','SPS/RATE1',404,'');return;}
  if(!\IPS\mpcservers\CrewRating::allowed($person)){\IPS\Output::i()->error('Nie możesz ocenić tej osoby. Wymagane jest zalogowanie i spełnienie zasad oceniania; nie można oceniać siebie.','SPS/RATE2',403,'');return;}
  $current=NULL;try{$current=\IPS\Db::i()->select('score','mpcservers_crew_ratings',array('person_id=? AND member_id=?',$person['id'],\IPS\Member::loggedIn()->member_id))->first();}catch(\UnderflowException $e){}
  $form=new \IPS\Helpers\Form('sps_person_rating','save');
  $field=new \IPS\Helpers\Form\Radio('sps_person_score',$current,TRUE,array('options'=>array(1=>'1 / 5',2=>'2 / 5',3=>'3 / 5',4=>'4 / 5',5=>'5 / 5')));$field->label='Twoja ocena';$field->description='Jedna ocena na osobę. Zapisanie nowej wartości zastępuje Twój poprzedni głos.';$form->add($field);
  if($values=$form->values()){\IPS\Session::i()->csrfCheck();\IPS\mpcservers\CrewRating::save($person['id'],$values['sps_person_score']);\IPS\Output::i()->redirect(\IPS\mpcservers\Crew::url(array('id'=>$person['id'])));return;}
  \IPS\Output::i()->httpHeaders['Cache-Control']='private, no-store';\IPS\Output::i()->title='Oceń: '.$person['name'];\IPS\Output::i()->output='<section class="ipsBox i-padding_3"><h1>Oceń: '.\IPS\mpcservers\Directory::esc($person['name']).'</h1>'.(string)$form.'</section>';
 }
 protected function lock(){
  try{$topic=\IPS\forums\Topic::load((int)\IPS\Request::i()->tid);}catch(\OutOfRangeException $e){\IPS\Output::i()->error('Nie znaleziono publikacji.','SPS/LOCK1',404,'');}
  if(!\IPS\mpcservers\CrewLock::canModerate($topic) || !\IPS\mpcservers\Presentation::enabled($topic->forum_id) || \IPS\mpcservers\Presentation::isDirectory($topic)){\IPS\Output::i()->error('Brak uprawnień.','SPS/LOCK2',403,'');}
  $state=\IPS\mpcservers\CrewLock::get($topic->tid);
  $form=new \IPS\Helpers\Form('sps_crew_lock','save');
  $field=new \IPS\Helpers\Form\YesNo('sps_lock_enabled',!empty($state['locked']),FALSE);$field->label='Zablokuj ekipę przed zmianami autora';$field->description='Blokada obejmuje wszystkie osoby, role i daty współpracy w tej publikacji. Uprawnieni moderatorzy nadal mogą je poprawiać.';$form->add($field);
  $reason=new \IPS\Helpers\Form\TextArea('sps_lock_reason',$state['reason']??'',FALSE,array('maxLength'=>500));$reason->label='Uzasadnienie (widoczne w publikacji)';$form->add($reason);
  if($values=$form->values()){\IPS\Session::i()->csrfCheck();\IPS\mpcservers\CrewLock::set($topic,$values['sps_lock_enabled'],$values['sps_lock_reason']);\IPS\Output::i()->redirect($topic->url());}
  \IPS\Output::i()->title='Blokada ekipy: '.$topic->title;\IPS\Output::i()->output=(string)$form;
 }
 protected function edit(){
  try{$topic=\IPS\forums\Topic::load((int)\IPS\Request::i()->tid);}catch(\OutOfRangeException $e){\IPS\Output::i()->error('Nie znaleziono serwera.','SPS/CREW1',404,'');}
  if(!\IPS\mpcservers\Crew::canManage($topic)||!\IPS\mpcservers\Presentation::visible($topic,\IPS\mpcservers\Presentation::get($topic->tid)?:array())){\IPS\Output::i()->error('Brak uprawnień.','SPS/CREW2',403,'');}
  $form=new \IPS\Helpers\Form('sps_crew_edit','save');$form->add(\IPS\mpcservers\CrewForm::field($topic,$topic->forum_id));
  if($values=$form->values()){\IPS\Session::i()->csrfCheck();\IPS\mpcservers\Crew::sync($topic,\IPS\mpcservers\Crew::normalize($values['sps_crew'],$topic,$topic->forum_id));\IPS\Output::i()->redirect($topic->url());}
  \IPS\Output::i()->title='Ekipa: '.$topic->title;
  $e=array('IPS\\mpcservers\\Directory','esc');$game=\IPS\mpcservers\Presentation::forumConfig($topic->forum_id)['category'];
  \IPS\Output::i()->output='<section class="sps-directory sps-crew-page ipsBox i-padding_3"><header class="sps-list-heading"><h1 class="sps-crew-page-title">Ekipa serwera</h1>'.\IPS\mpcservers\Directory::menu($game).'</header><a class="sps-crew-topic" href="'.$e($topic->url()).'"><i class="fa-solid fa-angle-left" aria-hidden="true"></i> '.$e($topic->title).'</a>'.(string)$form.'</section>'.\IPS\mpcservers\Directory::assets();
 }
 protected function manage(){\IPS\Output::i()->title='Osoby i ekipy serwerów';\IPS\Output::i()->httpHeaders['Cache-Control']='private, no-store';\IPS\Output::i()->output=\IPS\mpcservers\CrewDirectory::render();}
}
