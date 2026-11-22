<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Compatibility route. Authorization is enforced by the canonical controller. */
class people extends \IPS\mpcservers\modules\front\people\browse {}
