<?php
namespace IPS\mpcservers\modules\admin\rankings;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class settings extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\ModuleAccess::requireAdmin('rankings');parent::execute();}
 protected function manage(){\IPS\Output::i()->title='MPCServerSystem - '.\IPS\mpcservers\ModuleAccess::definitions()['rankings']['label'];\IPS\Output::i()->output=\IPS\mpcservers\ModuleAdmin::render('rankings');}
}
