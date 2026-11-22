<?php
namespace IPS\mpcservers\Modules\Metin2;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\Status;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ProxyAjax
{
    public static function token($topic,$forum,$source,$member,$draft)
    {
        $secret=ProxySecret::key(FALSE);
        if(strlen($secret)<32)throw new \RuntimeException('Proxy key not initialized');
        $payload=base64_encode(json_encode(array('tid'=>$topic?(int)$topic->tid:0,'forum'=>(int)$forum,'source'=>$source,'member'=>(int)$member,'draft'=>(bool)$draft,'expires'=>time()+1800)));
        return $payload.'.'.hash_hmac('sha256',$payload,$secret);
    }
    public static function verify($token)
    {
        if(!is_string($token)||strlen($token)>6000)throw new \OutOfRangeException;
        $parts=explode('.',$token);$secret=ProxySecret::key(FALSE);
        if(count($parts)!==2||strlen($secret)<32||!hash_equals(hash_hmac('sha256',$parts[0],$secret),$parts[1]))throw new \OutOfRangeException;
        $data=json_decode(base64_decode($parts[0],TRUE),TRUE);
        if(!$data||$data['expires']<time()||!\IPS\Settings::i()->sps_proxy_ajax||!\IPS\Settings::i()->sps_proxy_js)throw new \OutOfRangeException;
        $previous=\IPS\Member::$loggedInMember;
        try{
            \IPS\Member::$loggedInMember=$data['member']?\IPS\Member::load($data['member']):new \IPS\Member;
            $topic=$data['tid']?Presentation::topic($data['tid']):NULL;
            if($data['tid']&&(!$topic||!$topic->canView()||$topic->hidden()||(int)$topic->forum_id!==$data['forum']))throw new \OutOfRangeException;
            if($data['draft']){
                if(!$data['member']||($topic?!$topic->canEdit():!\IPS\forums\Topic::canCreate(\IPS\Member::loggedIn(),\IPS\forums\Forum::load($data['forum']))))throw new \OutOfRangeException;
            }else{
                if(!$topic)throw new \OutOfRangeException;$row=Presentation::get($topic->tid);
                if(!$row||!Presentation::visible($topic,$row)||$row['mode']!=='html'||$row['url']!==$data['source'])throw new \OutOfRangeException;
            }
            if(!Presentation::enabled($data['forum'])||!Presentation::linkModeEnabled(Presentation::forumConfig($data['forum']))||!Presentation::allowedUrl($data['source'],$data['forum']))throw new \OutOfRangeException;
        }finally{\IPS\Member::$loggedInMember=$previous;}
        return $data;
    }
    public static function target($url,$scope)
    {
        $p=parse_url($url);$base=parse_url($scope['source']);$path=rawurldecode($p['path']??'/');$root=$base['path']??'/';$root=substr($root,0,strrpos($root,'/')+1);
        if(!$p||($p['scheme']??'')!=='https'||strtolower($p['host']??'')!==strtolower($base['host'])||isset($p['user'])||isset($p['pass'])||isset($p['port'])||preg_match('/[\x00-\x20\\\\]/',$url)||strpos($path,$root)!==0||preg_match('~(?:^|/)\.\.(?:/|$)|%2e|%2f|%5c~i',$path))throw new \OutOfRangeException;
        parse_str($p['query']??'',$query);
        foreach(array('do','action','act','delete','logout','login','password','auth','token','key') as $name)if(array_key_exists($name,$query))throw new \OutOfRangeException;
        // Never relay CSRF tokens or side-effect flags copied from the remote page.
        unset($query['csrfKey'],$query['csrf'],$query['rating_submitted']);
        // Collapse only harmless dot segments after validation; traversal remains forbidden.
        $path=preg_replace('~/(?:\./)+~','/',$p['path']??'/');
        return 'https://'.strtolower($p['host']).$path.($query?'?'.http_build_query($query):'');
    }
    public static function response($url,$scope,$asset=FALSE)
    {
        $fetchScope=$scope;if($asset)$fetchScope['source']='https://'.parse_url($scope['source'],PHP_URL_HOST).'/';
        $url=static::target($url,$fetchScope);if($asset&&!preg_match('~\.(css|woff2?|m?js|glb|png|jpe?g|webp|gif)$~i',parse_url($url,PHP_URL_PATH)))throw new \OutOfRangeException;$dir=sys_get_temp_dir().'/mpcs_ajax_'.substr(hash('sha256',\IPS\ROOT_PATH),0,16);
        if(!is_dir($dir))mkdir($dir,0700,TRUE);
        // One bounded cache per target, shared across viewers. No member credentials reach the remote site.
        $key=hash('sha256',$scope['forum'].$scope['source']).'-'.(hexdec(substr(hash('sha256',($asset?'asset:':'').$url),0,4))%32);$file=$dir.'/'.$key.'.json';$cached=is_file($file)?json_decode(file_get_contents($file),TRUE):NULL;
        if($cached&&($cached['request']??'')===$url&&$cached['expires']>time()&&($cached['asset']??FALSE)===$asset){if($asset)$cached['body']=base64_decode($cached['body']);return $cached;}
        $lock=fopen($dir.'/'.hash('sha256',$scope['source']).'.lock','c');if(!$lock)throw new \RuntimeException('Busy');$until=microtime(TRUE)+4;while(!flock($lock,LOCK_EX|LOCK_NB)){if(microtime(TRUE)>$until){fclose($lock);throw new \RuntimeException('Busy');}usleep(50000);}
        try{
            $cached=is_file($file)?json_decode(file_get_contents($file),TRUE):NULL;
            if($cached&&($cached['request']??'')===$url&&$cached['expires']>time()&&($cached['asset']??FALSE)===$asset){if($asset)$cached['body']=base64_decode($cached['body']);return $cached;}
            $rate=$dir.'/'.hash('sha256',$scope['source']).'.rate';$count=is_file($rate)?json_decode(file_get_contents($rate),TRUE):NULL;
            if(!$count||$count['start']<time()-60)$count=array('start'=>time(),'count'=>0);
            if(++$count['count']>30)throw new \RuntimeException('Rate limit');file_put_contents($rate,json_encode($count));
            $result=PresentationProxy::download($url,$scope['forum'],$fetchScope,$asset);$result['expires']=time()+3600;$result['request']=$url;
            $stored=$result;$stored['asset']=$asset;if($asset)$stored['body']=base64_encode($stored['body']);file_put_contents($file,json_encode($stored),LOCK_EX);chmod($file,0600);return $result;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
    public static function bridge($html,$token,$source)
    {
        $html=ProxyAssets::html($html,$source,$token);
        $config=json_encode(array('endpoint'=>(string)\IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxyajax','front'),'token'=>$token,'source'=>$source),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $js=file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/interface/proxy-bridge.js');
        return preg_replace_callback('~<head\b[^>]*>~i',function($m)use($js,$config){return $m[0]. '<script data-cfasync="false">(function(config){' .$js.'})('.$config.');</script>';},$html,1);
    }
    public static function cleanup()
    {
        $dir=sys_get_temp_dir().'/mpcs_ajax_'.substr(hash('sha256',\IPS\ROOT_PATH),0,16);if(!is_dir($dir))return;
        foreach(new \DirectoryIterator($dir) as $f)if($f->isFile()&&substr($f->getFilename(),-5)==='.json'&&$f->getMTime()<time()-86400)@unlink($f->getPathname());
    }
}
