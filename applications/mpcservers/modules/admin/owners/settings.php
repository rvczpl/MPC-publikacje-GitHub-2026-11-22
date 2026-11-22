<?php
namespace IPS\mpcservers\modules\admin\owners;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\ModuleAccess::requireAdmin('owners');parent::execute();}
 protected function manage(){\IPS\Output::i()->title='MPCServerSystem - '.\IPS\mpcservers\ModuleAccess::definitions()['owners']['label'];\IPS\Output::i()->output=\IPS\mpcservers\ModuleAdmin::render('owners');}
}
