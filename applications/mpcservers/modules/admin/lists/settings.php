<?php
namespace IPS\mpcservers\modules\admin\lists;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller { public static $csrfProtected=TRUE; public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('lists');parent::execute();} protected function manage(){\IPS\Output::i()->title='Listy serwerów';\IPS\Output::i()->output=\IPS\mpcservers\Modules\Core\ModuleSettings::render('lists');} }
