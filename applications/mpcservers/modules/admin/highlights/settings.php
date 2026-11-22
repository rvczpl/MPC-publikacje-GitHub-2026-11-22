<?php
namespace IPS\mpcservers\modules\admin\highlights;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller { public static $csrfProtected=TRUE; public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('highlights');parent::execute();} protected function manage(){\IPS\Output::i()->title='MPC Poleca i TOP serwery';\IPS\Output::i()->output=\IPS\mpcservers\Modules\Core\ModuleSettings::render('highlights');} }
