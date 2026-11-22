<?php
namespace IPS\mpcservers\modules\admin\servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class presentation extends \IPS\Dispatcher\Controller {
 public static $csrfProtected=TRUE;
 public function execute(): void{\IPS\mpcservers\ModuleAccess::requireAdmin('owners');\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module=owners&controller=settings'));}
}
