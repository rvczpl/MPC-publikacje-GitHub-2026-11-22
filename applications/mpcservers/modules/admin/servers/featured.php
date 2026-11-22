<?php
namespace IPS\mpcservers\modules\admin\servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
use IPS\mpcservers\Presentation;
use IPS\mpcservers\Recommended;
use IPS\mpcservers\Directory;
class featured extends \IPS\Dispatcher\Controller
{
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('highlights');parent::execute();}
 protected function manage(){
  $base=\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=recommended');$cats=\IPS\mpcservers\Modules\Core\Registry::games('metin2')+\IPS\mpcservers\Modules\Core\Registry::games('minecraft');
  $game=(string)\IPS\Request::i()->game;if(!isset($cats[$game]))$game=(string)array_key_first($cats);
  \IPS\Output::i()->title='MPC Poleca - publikacje';
  $html='<div class="i-padding_3"><a href="'.Directory::esc(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=settings')).'">Ustawienia modułu MPC Poleca i TOP</a><p>Wybierz grę, a następnie publikację. Polecenie można też zmieniać bezpośrednio w temacie.</p><nav class="i-margin-bottom_3">';
  foreach($cats as $key=>$name)$html.='<a class="ipsButton ipsButton--small '.($key===$game?'ipsButton--primary':'ipsButton--inherit').'" href="'.Directory::esc($base->setQueryString('game',$key)).'">'.Directory::esc($name).'</a> ';
  $html.='</nav><h2>'.Directory::esc($cats[$game]??'').'</h2><table class="ipsTable ipsTable_zebra"><thead><tr><th>Publikacja</th><th>Polecenie</th><th></th></tr></thead><tbody>';
  $items=array();foreach(\IPS\Db::i()->select('*','mpcservers_presentations',array('is_server=1 AND category=?',$game),'featured DESC,tid DESC') as $row){try{$t=Presentation::topic($row['tid']);}catch(\OutOfRangeException $e){continue;}$items[]=array($row,$t);}
  $pages=max(1,(int)ceil(count($items)/30));$page=max(1,min($pages,(int)\IPS\Request::i()->page));
  foreach(array_slice($items,($page-1)*30,30) as list($row,$t)){
   $html.='<tr><td><a href="'.Directory::esc($t->url()).'">'.Directory::esc($t->title).'</a></td><td>'.($row['featured']?Recommended::badge():'Brak polecenia').'</td><td><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.Directory::esc($base->setQueryString(array('do'=>'edit','id'=>$t->tid))).'">'.($row['featured']?'Zmień polecenie':'Poleć serwer').'</a></td></tr>';
  }
  if(!$items)$html.='<tr><td colspan="3">Brak publikacji tej gry.</td></tr>';
  $html.='</tbody></table><nav class="i-padding_3">';if($page>1)$html.='<a href="'.Directory::esc($base->setQueryString(array('game'=>$game,'page'=>$page-1))).'">Poprzednia</a> ';
  $html.='Strona '.$page.' z '.$pages;if($page<$pages)$html.=' <a href="'.Directory::esc($base->setQueryString(array('game'=>$game,'page'=>$page+1))).'">Następna</a>';
  \IPS\Output::i()->output=$html.'</nav></div>';
 }
 protected function edit(){
  try{$topic=Presentation::topic((int)\IPS\Request::i()->id);}catch(\OutOfRangeException $e){\IPS\Output::i()->error('Nie znaleziono publikacji.','SPS/RECOMMENDACP',404,'');}
  if(!Recommended::canManage($topic)){\IPS\Output::i()->error('Brak uprawnienia do polecania.','SPS/RECOMMENDPERM',403,'');}
  $row=Presentation::get($topic->tid);$form=new \IPS\Helpers\Form('sps_recommend_publication','save');
  $f=new \IPS\Helpers\Form\YesNo('sps_recommend_on',!empty($row['featured']),FALSE);$f->label='MPC Poleca';$form->add($f);
  if($v=$form->values()){\IPS\Session::i()->csrfCheck();Recommended::set($topic,$v['sps_recommend_on']);\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=recommended')->setQueryString('game',$row['category']),'saved');}
  \IPS\Output::i()->title='Polecenie: '.$topic->title;\IPS\Output::i()->output=(string)$form;
 }
}
