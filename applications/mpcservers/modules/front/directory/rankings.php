<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class rankings extends \IPS\Dispatcher\Controller { public function execute(): void{\IPS\mpcservers\Modules\Core\Registry::front('highlights');\IPS\Output::i()->redirect(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=browse','front'));} }
