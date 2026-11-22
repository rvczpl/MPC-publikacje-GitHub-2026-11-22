<?php
namespace IPS\mpcservers\modules\admin\people;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\ModuleAccess::requireAdmin('people');parent::execute();}
 protected function manage(){\IPS\Output::i()->title='MPCServerSystem - '.\IPS\mpcservers\ModuleAccess::definitions()['people']['label'];\IPS\Output::i()->output=\IPS\mpcservers\ModuleAdmin::render('people');}
}
