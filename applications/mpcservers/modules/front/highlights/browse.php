<?php
namespace IPS\mpcservers\modules\front\highlights;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class browse extends \IPS\Dispatcher\Controller {
 public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::front('highlights');parent::execute();}
 protected function manage(){
  $cats=\IPS\mpcservers\ModeFilters::activeGames(\IPS\mpcservers\Presentation::forumIds());$requested=(string)(\IPS\Request::i()->category?:\IPS\Request::i()->game);$game=isset($cats[$requested])?$requested:'';
  $mode=is_scalar(\IPS\Request::i()->mode)?(string)\IPS\Request::i()->mode:'';$options=$game?\IPS\mpcservers\Awards::options($game):array();if(!isset($options[$mode]))$mode='';
  $e=array('IPS\\mpcservers\\Directory','esc');$out=\IPS\mpcservers\Directory::assets().'<section class="sps-directory ipsBox i-padding_3"><header class="sps-list-heading"><h1 class="ipsTitle ipsTitle--h3">TOP serwery</h1>'.\IPS\mpcservers\Directory::menu($game,'top').'</header><form method="get" class="sps-filters" action="'.$e(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=browse','front')).'">';
  foreach(array('app'=>'mpcservers','module'=>'highlights','controller'=>'browse') as $k=>$v)$out.='<input type="hidden" name="'.$k.'" value="'.$v.'">';
  $out.=\IPS\mpcservers\ModeFilters::games($cats,$game).'<label>Tryb serwera<select name="mode"><option value="">Wszystkie tryby</option>';foreach($options as $key=>$label)$out.='<option value="'.$e($key).'"'.($key===$mode?' selected':'').'>'.$e($label).'</option>';
  $out.='</select></label><button class="ipsButton ipsButton--primary">Pokaż TOP</button></form>';
  $winners=\IPS\mpcservers\Highlights::winners($game);if($mode!=='')foreach($winners as $id=>&$entry){$entry['awards']=array_values(array_filter($entry['awards'],function($a)use($mode){return $a['mode_key']===$mode;}));if(!$entry['awards'])unset($winners[$id]);}unset($entry);
  $out.=$winners?\IPS\mpcservers\Highlights::cards($winners):'<p class="i-padding_3 i-color_soft">Brak wyróżnień za zakończone okresy dla tego wyboru.</p>';
  \IPS\Output::i()->title='TOP serwery';\IPS\Output::i()->output=$out.'</section>';
 }
}
