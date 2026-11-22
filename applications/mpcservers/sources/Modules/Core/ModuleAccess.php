<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ModuleAccess {
 public static function aliases($name){return array('directory'=>'lists','owners'=>'community','people'=>'community','rankings'=>'highlights')[$name]??$name;}
 public static function definitions(){$out=Registry::definitions();foreach($out as $key=>&$v)$v['module']=$key;unset($v);foreach(array('directory','owners','people','rankings') as $key)$out[$key]=$out[static::aliases($key)];return $out;}
 public static function can($name){return Registry::can(static::aliases($name));}
 public static function requireAdmin($name){Registry::admin(static::aliases($name));}
}
