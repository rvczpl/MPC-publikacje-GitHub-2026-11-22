<?php
namespace IPS\mpcservers\tasks;
use IPS\mpcservers\Modules\Core\BackgroundJobs;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class status extends \IPS\Task
{
    public function execute(): mixed
    {
        BackgroundJobs::run('cleanup', function () {
            \IPS\mpcservers\Modules\Metin2\PresentationProxy::cleanup();
            \IPS\mpcservers\Modules\Metin2\ProxyAjax::cleanup();
            $db=\IPS\Db::i();
            $db->delete('mpcservers_rewards',['expires<?',time()-7*86400]);
            $db->delete('mpcservers_votes',['created<?',time()-8*86400]);
        });
        BackgroundJobs::run('awards', fn()=>\IPS\mpcservers\Awards::settle());
        BackgroundJobs::run('status', fn()=>\IPS\mpcservers\Modules\Core\StatusMonitor::run());
        return NULL;
    }
    public function cleanup() {}
}
