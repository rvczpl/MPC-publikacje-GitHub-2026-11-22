<?php
namespace IPS\mpcservers\Modules\Metin2;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Policy {
 public static function profile($cfg){return array('connection'=>!empty($cfg['tcp']),'port'=>'','protocols'=>array('none'=>'Bez sprawdzania','tcp'=>'Dostępność portu TCP'),'host_label'=>'Adres usługi do kontroli TCP','port_label'=>'Port usługi TCP');}
}
