<?php
namespace IPS\mpcservers\Modules\Minecraft;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Policy {
 public static function profile($cfg){return array('connection'=>TRUE,'port'=>25565,'protocols'=>array('none'=>'Bez sprawdzania','minecraft'=>'Minecraft Java'),'host_label'=>'Adres serwera Minecraft','port_label'=>'Port Minecraft Java');}
}
