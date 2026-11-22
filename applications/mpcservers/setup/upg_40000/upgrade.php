<?php
namespace IPS\mpcservers\setup\upg_40000;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade { public function step1(){\IPS\mpcservers\SystemMigration::run();return TRUE;} }
