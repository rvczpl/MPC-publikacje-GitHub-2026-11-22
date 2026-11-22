<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Compatibility route. Authorization is enforced by the canonical controller. */
class owners extends \IPS\mpcservers\modules\front\owners\panel {}
