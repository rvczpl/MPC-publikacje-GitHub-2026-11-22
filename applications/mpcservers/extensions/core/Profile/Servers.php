<?php
namespace IPS\mpcservers\extensions\core\Profile;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Servers extends \IPS\Extensions\ProfileAbstract
{
 protected ?\IPS\Member $member = NULL;
 public function __construct(\IPS\Member $member){$this->member=$member;}
 public function showTab(): bool{return (bool)\IPS\mpcservers\CrewProfile::records($this->member);}
 public function render(): string{return \IPS\mpcservers\CrewProfile::render($this->member);}
}
