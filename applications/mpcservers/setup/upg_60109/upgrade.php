<?php
namespace IPS\mpcservers\setup\upg_60109;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade
{
 public function step1(): bool { \IPS\mpcservers\Modules\Core\BackgroundJobs::install(); return TRUE; }
}
