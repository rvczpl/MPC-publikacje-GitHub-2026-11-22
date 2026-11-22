<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Media
{
 public static function managed($value){return is_string($value)&&$value!==''&&!preg_match('~^https?://~i',$value);}
 public static function url($value){if(!$value)return '';if(!static::managed($value))return Presentation::allowedIcon($value)?$value:'';try{return (string)\IPS\File::get('mpcservers_Graphics',$value)->url;}catch(\Exception $e){return '';}}
 public static function field($name,$old,$id=NULL){
  $default=NULL;if(static::managed($old)){try{$default=\IPS\File::get('mpcservers_Graphics',$old);}catch(\Exception $e){}}
  $f=new \IPS\Helpers\Form\Upload($name,$default,FALSE,array('storageExtension'=>'mpcservers_Graphics','storageContainer'=>'server_graphics','image'=>TRUE,'allowedFileTypes'=>array('gif','jpeg','jpe','jpg','png','webp'),'maxFileSize'=>5,'multiple'=>FALSE,'minimize'=>FALSE,'retainDeleted'=>TRUE),NULL,NULL,NULL,$id);
  if($old && !static::managed($old))$f->description='Obecna grafika pozostanie do czasu wgrania nowej.';
  return $f;
 }
 public static function value($value,$old=''){
  if($value instanceof \IPS\File)return (string)$value;
  // Existing remote images are preserved without downloading them to the server.
  return $old && !static::managed($old)?$old:'';
 }
 public static function references(){
  $out=array();foreach(array('sps_game_icons','sps_game_images') as $setting){foreach((array)json_decode((string)\IPS\Settings::i()->$setting,TRUE) as $v)if(static::managed($v))$out[]=$v;}
  foreach((array)json_decode((string)\IPS\Settings::i()->sps_forum_options,TRUE) as $cfg)foreach((array)($cfg['mode_images']??array()) as $group)foreach((array)$group as $v)if(static::managed($v))$out[]=$v;
  return array_values(array_unique($out));
 }
 public static function replaceReference($old,$new){
  $updates=array();foreach(array('sps_game_icons','sps_game_images','sps_forum_options') as $setting){$data=json_decode((string)\IPS\Settings::i()->$setting,TRUE)?:array();array_walk_recursive($data,function(&$v)use($old,$new){if($v===$old)$v=$new;});$updates[$setting]=json_encode($data,JSON_UNESCAPED_UNICODE);} \IPS\Settings::i()->changeValues($updates);
 }
 // Keep upload fields in their native IPS form rows, one below another.
 public static function assets(){return '';}
}
