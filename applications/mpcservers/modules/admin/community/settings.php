<?php
namespace IPS\mpcservers\modules\admin\community;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller { public static $csrfProtected=TRUE; public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('community');parent::execute();} protected function manage(){\IPS\Output::i()->title='Osoby, właściciele i powiązania';\IPS\Output::i()->output=\IPS\mpcservers\Modules\Core\ModuleSettings::render('community');} }
