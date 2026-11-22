<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
use IPS\mpcservers\Modules\Metin2\PresentationProxy;
use IPS\mpcservers\Presentation;
class proxy extends \IPS\Dispatcher\Controller
{
    /** Isolated document: bypass theme/SEO/consent output filters that inject scripts or replace CSP. */
    protected function emit($html,$status,$source='')
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        foreach(PresentationProxy::headers($html,$source) as $name=>$value)header($name.': '.$value,TRUE);
        echo $html;
        exit;
    }
    protected static function failure($error,$phase,$tid)
    {
        // No signed capabilities, source URLs or key values in diagnostics.
        try{\IPS\Log::log(array('topic'=>(int)$tid,'stage'=>$phase,'exception'=>get_class($error),'code'=>(int)$error->getCode()),'mpcservers_proxy');}catch(\Throwable $ignored){}
    }
    protected function manage()
    {
        $phase='permissions';
        try {
            $request=\IPS\Request::i();$tid=(int)$request->id;$draft=isset($request->source);
            $topic=$tid?Presentation::topic($tid):NULL;
            if($tid && (!$topic||!$topic->canView()||$topic->hidden()))throw new \OutOfRangeException;
            if($draft){
                \IPS\Session::i()->csrfCheck();$member=\IPS\Member::loggedIn();if(!$member->member_id)throw new \OutOfRangeException;
                $forum=$topic?$topic->forum_id:(int)$request->forum;
                if($topic?!$topic->canEdit():!\IPS\forums\Topic::canCreate($member,\IPS\forums\Forum::load($forum)))throw new \OutOfRangeException;
                if(!is_string($request->source))throw new \OutOfRangeException;$url=$request->source;$key='draft-'.$member->member_id;
            }else{
                if(!$topic)throw new \OutOfRangeException;$row=Presentation::get($tid);
                if(!$row||!Presentation::visible($topic,$row)||$row['mode']!=='html')throw new \OutOfRangeException;
                $forum=$topic->forum_id;$url=$row['url'];$key='topic-'.$tid;
            }
            if(!Presentation::enabled($forum)||!Presentation::linkModeEnabled(Presentation::forumConfig($forum))||!Presentation::allowedUrl($url,$forum))throw new \OutOfRangeException;
            $phase='cache';$html=PresentationProxy::cached($url,$forum,$key,$draft);
            if(\IPS\Settings::i()->sps_proxy_js && \IPS\Settings::i()->sps_proxy_ajax){
                try{
                    $phase='ajax_sign';$cap=\IPS\mpcservers\Modules\Metin2\ProxyAjax::token($topic,$forum,$url,\IPS\Member::loggedIn()->member_id,$draft);
                    $phase='ajax_bridge';$html=\IPS\mpcservers\Modules\Metin2\ProxyAjax::bridge($html,$cap,$url);
                }catch(\Throwable $ajaxError){
                    static::failure($ajaxError,$phase,$tid);
                    $js=\IPS\Settings::i()->sps_proxy_js;\IPS\Settings::i()->sps_proxy_js=FALSE;
                    try{$html=PresentationProxy::sanitize($html,$url);}finally{\IPS\Settings::i()->sps_proxy_js=$js;}
                    $html=preg_replace('~<body\b[^>]*>~i','$0<p>Interaktywny podgląd jest chwilowo niedostępny. Otwórz pełną prezentację przyciskiem nad podglądem.</p>',$html,1);
                }
            }
            $this->emit($html,200,$url);
        }catch(\Throwable $e){
            static::failure($e,$phase,$tid??0);
            $status=$e instanceof \OutOfRangeException?403:503;
            $this->emit('<!doctype html><meta charset="utf-8"><p>Podgląd jest niedostępny. Sprawdź uprawnienia, ustawienia prezentacji lub spróbuj ponownie za chwilę.</p>',$status);
        }
    }
}
