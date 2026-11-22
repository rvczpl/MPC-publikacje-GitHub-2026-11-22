<?php
namespace IPS\mpcservers\setup\upg_30011;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade { public function step1(){\IPS\mpcservers\CrewLock::install();return TRUE;} }
