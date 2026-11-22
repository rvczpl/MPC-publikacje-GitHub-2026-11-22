<?php
namespace IPS\mpcservers\Modules\Core;
use IPS\mpcservers\Modules\Lists\ModeFilters;
use IPS\mpcservers\Modules\Highlights\Highlights;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ModuleSettings
{
 public static function render($module){
  Registry::admin($module);$definitions=Registry::definitions();$form=new \IPS\Helpers\Form('sls_module_'.$module,'save');
  $f=new \IPS\Helpers\Form\YesNo('sls_enabled',Registry::enabled($module));$f->label='Moduł aktywny';$f->description='Wyłączenie ukrywa funkcje i blokuje ich adresy. Dane i historia pozostają w bazie.';$form->add($f);
  $isGame=in_array($module,array('metin2','minecraft'),TRUE);$isHighlights=$module==='highlights';
  $games=$isGame?Registry::games($module):($isHighlights?Registry::games('metin2')+Registry::games('minecraft'):array());
  $features=json_decode((string)\IPS\Settings::i()->sls_game_features,TRUE)?:array();
  if($isGame&&Registry::can('highlights'))$form->addMessage('Serwer dnia, tygodnia, miesiąca i roku, TOP serwery oraz MPC Poleca ustawiasz w jednym miejscu: <a href="'.htmlspecialchars((string)\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=settings'),ENT_QUOTES,'UTF-8').'">MPC Poleca i TOP serwery</a>.','ipsMessage ipsMessage--info',FALSE);
  if($isHighlights&&!$games)$form->addMessage('Brak gier z działami publikacji. Najpierw przypisz dział do gry w module Metin2 lub Minecraft.','ipsMessage ipsMessage--warning',FALSE);
  foreach($games as $game=>$label){
   $form->addMessage('<h2 class="ipsTitle ipsTitle--h4">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</h2>','',FALSE);
   if($isGame){ModeFilters::gameIconField($form,$game,$label);ServerModes::fields($form,$game);}
   if($isHighlights){
    $f=new \IPS\Helpers\Form\YesNo('sls_recommended_'.$game,$features[$game]['recommended']??TRUE);$f->label='MPC Poleca';$f->description='Polecone serwery są wyróżniane na liście i w tematach tej gry.';$form->add($f);
    $f=new \IPS\Helpers\Form\YesNo('sls_top_'.$game,$features[$game]['top']??TRUE,FALSE,array('togglesOn'=>Highlights::fieldIds($game)));$f->label='TOP serwery i serwer okresu';$f->description='Podbicia, rankingi oraz serwer dnia, tygodnia, miesiąca i roku dla tej gry.';$form->add($f);
    Highlights::fields($form,$game,$label);
   }
   if($module==='minecraft')GameRewards::field($form,$game);if($module==='minecraft')\IPS\mpcservers\Modules\Minecraft\Versions::field($form,$game);
  }
  $legacy=array();if($isGame){foreach(Presentation::categories() as $key=>$label)if(Registry::kind($key)==='legacy')$legacy[$key]=$label;}
  if($legacy){$f=new \IPS\Helpers\Form\Select('sls_assign',array(),FALSE,array('options'=>$legacy,'multiple'=>TRUE));$f->label='Przypisz zachowane gry do tego modułu';$f->description='Nierozpoznane stare gry są zachowane, ale niewidoczne na listach. Przypisanie nie zmienia identyfikatorów ani historii.';$form->add($f);}
  if($module==='lists')FeatureHooks::fields($form);
  if($values=$form->values()){
   \IPS\Session::i()->csrfCheck();Registry::admin($module);$switches=json_decode((string)\IPS\Settings::i()->sls_modules,TRUE)?:array();$switches[$module]=(bool)$values['sls_enabled'];
   foreach($games as $game=>$label){if($isGame){ModeFilters::saveGameIcon($game,$values);ServerModes::save($game,$values);}if($isHighlights){foreach(array('recommended','top') as $key)$features[$game][$key]=(bool)$values['sls_'.$key.'_'.$game];Highlights::save($game,$values);}if($module==='minecraft')GameRewards::save($game,$values);if($module==='minecraft')\IPS\mpcservers\Modules\Minecraft\Versions::save($game,$values);}
   foreach((array)($values['sls_assign']??array()) as $key){if(!isset($legacy[$key]))throw new \DomainException('Nieprawidłowa gra.');Registry::bind($key,$module);}
   $updates=array('sls_modules'=>json_encode($switches),'sls_game_features'=>json_encode($features));
   if($module==='lists')$updates['mpcs_feature_hooks']=FeatureHooks::values($values);
   \IPS\Settings::i()->changeValues($updates);
   \IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module='.$module.'&controller=settings'),'saved');return '';
  }
  $out='<div class="ipsBox">'.$form.'</div>';
  if($module==='community')$out.=ModuleAdmin::render('people');
  return $out;
 }
}
