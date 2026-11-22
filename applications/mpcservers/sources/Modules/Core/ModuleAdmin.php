<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\ModeFilters;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
use IPS\mpcservers\Modules\Highlights\Highlights;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Core\ModuleAccess;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ModuleAdmin
{
 public static function render($module){
  if($module==='rankings'){\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=settings'));return '';}
  ModuleAccess::requireAdmin($module);$game=\IPS\Request::i()->game;$game=is_scalar($game)?(string)$game:'';$cats=ModeFilters::activeGames(Presentation::forumIds());if($game!==''&&!isset($cats[$game]))throw new \OutOfRangeException;
  $form=new \IPS\Helpers\Form('mpcs_'.$module,'save');$form->addMessage('Ustawienia dotyczą rodzaju gry i są wspólne dla jego działów publikacji.');
  if($module==='people'){$f=new \IPS\Helpers\Form\Select('sps_crew_rating_access',(string)\IPS\Settings::i()->sps_crew_rating_access,FALSE,array('options'=>array('all'=>'Wszyscy zalogowani','team'=>'Współpracownicy z tej samej ekipy')));$f->label='Kto może oceniać osoby';$f->description='Jeden edytowalny głos 1-5 na osobę. Bez samooceny. Współpracownicy muszą mieć nakładające się okresy współpracy. Zmiana trybu nie usuwa historii ocen.';$form->add($f);}
  foreach($game?array($game=>$cats[$game]):$cats as $key=>$label){
   if($module==='owners'){if(GameRewards::supports($key))GameRewards::field($form,$key);else $form->addMessage(Directory::esc($label).': nagrody niedostępne. Nazwa gry musi zawierać Minecraft.');}
   if($module==='people'){$lines=array();foreach(Crew::roles($key) as $role=>$name)$lines[]=$role.'='.$name;$f=new \IPS\Helpers\Form\TextArea('sps_roles_'.$key,implode("\n",$lines),FALSE,array(),function($v){foreach(preg_split('/\R/',trim($v)) as $line)if(trim($line)!==''&&!preg_match('/^[a-z0-9_]{1,40}=.{1,120}$/uD',trim($line)))throw new \DomainException('Jedna rola w wierszu: identyfikator=Nazwa.');});$f->label='Role ekipy: '.$label;$form->add($f);}
  }
  if($values=$form->values()){\IPS\Session::i()->csrfCheck();ModuleAccess::requireAdmin($module);$roles=json_decode((string)\IPS\Settings::i()->sps_crew_roles,TRUE)?:array();foreach($game?array($game=>$cats[$game]):$cats as $key=>$label){if($module==='owners')GameRewards::save($key,$values);if($module==='people'){$roles[$key]=array();foreach(preg_split('/\R/',(string)$values['sps_roles_'.$key]) as $line)if(strpos($line,'=')!==FALSE){list($id,$name)=explode('=',trim($line),2);$roles[$key][$id]=$name;}}}
   if($module==='people')\IPS\Settings::i()->changeValues(array('sps_crew_rating_access'=>$values['sps_crew_rating_access'],'sps_crew_roles'=>json_encode($roles,JSON_UNESCAPED_UNICODE)));
   \IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module='.$module.'&controller=settings'),'saved');return '';
  }
  return (string)$form;
 }
}
