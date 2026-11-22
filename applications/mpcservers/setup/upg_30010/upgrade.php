<?php
namespace IPS\mpcservers\setup\upg_30010;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade { public function step1(){\IPS\mpcservers\Awards::install();return TRUE;} }
