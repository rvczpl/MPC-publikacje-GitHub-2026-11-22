<?php
namespace IPS\mpcservers\modules\admin\metin2;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller { public static $csrfProtected=TRUE; public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::admin('metin2');parent::execute();} protected function manage(){\IPS\Output::i()->title='Metin2';\IPS\Output::i()->output=\IPS\mpcservers\Modules\Core\ModuleSettings::render('metin2');} }
