<?php
namespace IPS\mpcservers\modules\front\owners;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class rewards extends \IPS\Dispatcher\Controller
{
    // This machine endpoint uses a per-server secret, never a browser session.
    public static $csrfProtected=TRUE;
    protected function manage()
    {
        \IPS\Output::i()->httpHeaders['Cache-Control']='no-store';
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { \IPS\Output::i()->json(array('ok'=>FALSE),405); return; }
        $key=(string)($_SERVER['HTTP_X_SPS_KEY']??'');
        $ok=\IPS\mpcservers\Boost::claim((int)\IPS\Request::i()->server,$key,(string)\IPS\Request::i()->code,(string)\IPS\Request::i()->player);
        \IPS\Output::i()->json(array('ok'=>$ok),$ok?200:403);
    }
}
