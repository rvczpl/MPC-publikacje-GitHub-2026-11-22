<?php
namespace IPS\mpcservers\setup\upg_40203;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade { public function step1(){ \IPS\Application::load('mpcservers')->installSettings(); \IPS\mpcservers\Modules\Metin2\ProxySecret::key(); return TRUE; } }
