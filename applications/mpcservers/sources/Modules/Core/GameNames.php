<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class GameNames
{
 public static function plan($forum,$existing,$name){
  $name=trim($name);if($name===''||mb_strlen($name)>80||preg_match('/[\r\n=<>\x00-\x1f]/u',$name))throw new \DomainException('Podaj nazwę gry (do 80 znaków, w jednym wierszu).');
  $cats=Presentation::categories();$matching=NULL;foreach($cats as $key=>$label){if(mb_strtolower($label)===mb_strtolower($name)){$matching=$key;break;}}
  $key=$matching??($existing?:('game_'.(int)$forum));$cats[$key]=$name;return array('key'=>$key,'categories'=>$cats,'reuse'=>$matching!==NULL && $matching!==$existing);
 }
 public static function save($forum,$existing,$name){
  $plan=static::plan($forum,$existing,$name);$lines=array();foreach($plan['categories'] as $key=>$label)$lines[]=$key.'='.$label;
  \IPS\Settings::i()->changeValues(array('sps_categories'=>implode("\n",$lines)));return $plan['key'];
 }
}
