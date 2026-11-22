<?php
namespace IPS\mpcservers\modules\front\directory;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class proxyajax extends \IPS\Dispatcher\Controller
{
    protected function manage()
    {
        // Opaque preview frames may need HTTP authentication on staging hosts.
        // The signed capability is still mandatory; cookies are never relayed upstream.
        if(($_SERVER['HTTP_ORIGIN']??'')==='null'){header('Access-Control-Allow-Origin: null');header('Access-Control-Allow-Credentials: true');}else{header('Access-Control-Allow-Origin: *');}
        header('Vary: Origin');header('Access-Control-Allow-Methods: GET, OPTIONS');header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: sandbox; default-src 'none'; frame-ancestors 'none'");
        if(($_SERVER['REQUEST_METHOD']??'')==='OPTIONS'){http_response_code(204);exit;}
        $scope=NULL;$stage='verify';
        try{
            if(($_SERVER['REQUEST_METHOD']??'')!=='GET')throw new \OutOfRangeException;
            $scope=\IPS\mpcservers\Modules\Metin2\ProxyAjax::verify(\IPS\Request::i()->cap);
            $url=\IPS\Request::i()->target;if(!is_string($url)||strlen($url)>2000)throw new \OutOfRangeException;
            $asset=(string)\IPS\Request::i()->asset==='1';
            $stage='download';
            $result=\IPS\mpcservers\Modules\Metin2\ProxyAjax::response($url,$scope,$asset);
            $stage='rewrite';
            if($asset&&$result['type']==='text/css')$result['body']=\IPS\mpcservers\Modules\Metin2\ProxyAssets::css($result['body'],$result['url'],\IPS\Request::i()->cap,$scope['source']);
            if($asset&&$result['type']==='text/javascript')$result['body']=\IPS\mpcservers\Modules\Metin2\ProxyAssets::javascript($result['body'],$result['url'],\IPS\Request::i()->cap,$scope['source']);
            if(!is_string($result['body']))throw new \RuntimeException('Resource rewrite failed');
            header('Content-Type: '.$result['type'].'; charset=UTF-8');echo $result['body'];exit;
        }catch(\Throwable $e){
            $status=502;$code='upstream_unavailable';$retry=0;
            if($e instanceof \OutOfRangeException){$status=403;$code='forbidden';}
            elseif($e->getMessage()==='Rate limit'){$status=429;$code='rate_limit';$retry=60;}
            elseif($e->getMessage()==='Busy'){$status=503;$code='busy';$retry=5;}
            elseif($stage==='rewrite'){$status=500;$code='rewrite_failed';}
            // Do not record capability tokens, query strings, cookies or upstream response bodies.
            if($scope && $status!==403){
                try{\IPS\Log::log(array('topic'=>(int)($scope['tid']??0),'forum'=>(int)$scope['forum'],'stage'=>$stage,'code'=>$code,'host'=>parse_url($scope['source'],PHP_URL_HOST),'resource'=>substr((string)parse_url($url??'',PHP_URL_PATH),0,200),'exception'=>get_class($e),'reason'=>substr(preg_replace('/[\r\n]/',' ',$e->getMessage()),0,200)),'mpcservers_proxy_asset');}catch(\Throwable $ignored){}
            }
            http_response_code($status);header('Content-Type: application/json; charset=UTF-8');header('X-MPC-Proxy-Error: '.$code);
            if($retry)header('Retry-After: '.$retry);
            echo json_encode(array('error'=>'Nie można pobrać tej części prezentacji.','code'=>$code),JSON_UNESCAPED_UNICODE);exit;
        }
    }
}
