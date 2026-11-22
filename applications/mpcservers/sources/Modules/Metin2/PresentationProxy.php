<?php
namespace IPS\mpcservers\Modules\Metin2;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\Status;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }

/** Bounded HTML mirror. Scripts run only in an opaque sandbox, never in the forum origin. */
class PresentationProxy
{
    /** Zwykła przeglądarka - strony serwerów i ich ochrona (np. Cloudflare) odrzucają nieznane boty */
    const USER_AGENT='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    const MAX_BYTES = 2097152;
    public static function url($tid)
    {
        return \IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxy','front')->setQueryString('id',(int)$tid);
    }
    public static function headers($html='', $source='')
    {
        $scripts="'none'";$connect="'none'";$sandbox='';
        if(\IPS\Settings::i()->sps_proxy_js && $source && $html){
            $origins=array();$host=parse_url($source,PHP_URL_HOST);
            if($host && preg_match('/^[a-z0-9.-]+$/iD',$host))$origins[]='https://'.$host;
            $doc=new \DOMDocument; $old=libxml_use_internal_errors(TRUE);
            try{$doc->loadHTML($html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
            foreach($doc->getElementsByTagName('script') as $script){
                $src=parse_url($script->getAttribute('src'));$host=$src['host']??'';
                if(($src['scheme']??'')==='https' && preg_match('/^[a-z0-9.-]+$/iD',$host) && !isset($src['port']) && count($origins)<16)$origins[]='https://'.$host;
            }
            $origins=array_values(array_unique($origins));
            if($origins){$scripts="'unsafe-inline' ".implode(' ',$origins);if(\IPS\Settings::i()->sps_proxy_ajax)$scripts.=' '.strtok((string)\IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxyajax','front'),'?');$connect=implode(' ',$origins);if(\IPS\Settings::i()->sps_proxy_ajax)$connect.=' '.strtok((string)\IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxyajax','front'),'?');$sandbox=' allow-scripts';}
        }
        return array('Content-Security-Policy'=>"sandbox".$sandbox." allow-popups allow-popups-to-escape-sandbox; default-src 'none'; script-src ".$scripts."; style-src https: 'unsafe-inline'; img-src https: data: blob:; font-src https: data:; media-src https:; connect-src ".$connect."; worker-src 'none'; frame-src 'none'; object-src 'none'; form-action 'none'; frame-ancestors 'self'",'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'SAMEORIGIN','Referrer-Policy'=>'no-referrer','Cache-Control'=>'private, no-store, no-transform','X-Robots-Tag'=>'noindex, nofollow, noarchive');
    }
    public static function absolute($value,$base)
    {
        $value=trim(html_entity_decode((string)$value,ENT_QUOTES|ENT_HTML5,'UTF-8'));
        if ($value==='' || preg_match('/[\x00-\x20\\\\]/',$value)) return '';
        if (preg_match('~^https://~i',$value)) return $value;
        if (strpos($value,'//')===0) return 'https:'.$value;
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i',$value)) return '';
        $b=parse_url($base);$origin='https://'.$b['host'];$path=$b['path']??'/';
        if ($value[0]==='#') return $base.$value;
        if ($value[0]==='?') return $origin.$path.$value;
        return $origin.($value[0]==='/'?$value:substr($path,0,strrpos($path,'/')+1).$value);
    }
    public static function validate($url,$forum)
    {
        if (strlen($url)>2000 || preg_match('/[\x00-\x20\\\\]/',$url) || !Presentation::allowedUrl($url,$forum)) throw new \RuntimeException('Niedozwolony adres prezentacji.');
        $host=strtolower(parse_url($url,PHP_URL_HOST));
        if ($host===strtolower(parse_url(\IPS\Settings::i()->base_url,PHP_URL_HOST))) throw new \RuntimeException('Nie można pobierać forum przez proxy.');
        $ip=Status::resolve($host);
        if (!$ip) throw new \RuntimeException('Adres prezentacji nie jest publiczny.');
        return array($host,$ip);
    }
    public static function download($url,$forum,$scope=NULL,$asset=FALSE)
    {
        if (!function_exists('curl_init')) throw new \RuntimeException('Brak obsługi proxy na hostingu.');
        $deadline=microtime(TRUE)+12;
        for($redirect=0;$redirect<3;$redirect++) {
            if($scope!==NULL)$url=ProxyAjax::target($url,$scope);
            list($host,$ip)=static::validate($url,$forum);$body='';$location='';$type='';$tooLarge=FALSE;$challenge=FALSE;
            $remaining=(int)(($deadline-microtime(TRUE))*1000);if($remaining<500)throw new \RuntimeException('Przekroczono czas pobierania.');
            $curl=curl_init($url);
            curl_setopt_array($curl,array(CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_PROXY=>'',CURLOPT_RESOLVE=>array($host.':443:'.$ip),CURLOPT_CONNECTTIMEOUT_MS=>2500,CURLOPT_TIMEOUT_MS=>min(7000,$remaining),CURLOPT_SSL_VERIFYPEER=>TRUE,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>self::USER_AGENT,CURLOPT_ENCODING=>'',CURLOPT_HTTPHEADER=>($scope===NULL?array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8','Accept-Language: pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7'):array('Accept: text/html,application/json','Accept-Language: pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7','X-Requested-With: XMLHttpRequest')),CURLOPT_HEADERFUNCTION=>function($c,$line)use(&$location,&$type,&$challenge){if(stripos($line,'cf-mitigated:')===0&&stripos($line,'challenge')!==FALSE)$challenge=TRUE;if(stripos($line,'Location:')===0)$location=trim(substr($line,9));if(stripos($line,'Content-Type:')===0)$type=trim(substr($line,13));return strlen($line);},CURLOPT_WRITEFUNCTION=>function($c,$chunk)use(&$body,&$tooLarge){if(strlen($body)+strlen($chunk)>self::MAX_BYTES){$tooLarge=TRUE;return 0;}$body.=$chunk;return strlen($chunk);}));
            $ok=curl_exec($curl);$code=curl_getinfo($curl,CURLINFO_HTTP_CODE);$errno=curl_errno($curl);curl_close($curl);
            if($ok===FALSE)throw new \RuntimeException($tooLarge?'Przekroczono limit 2 MiB po dekompresji.':'Błąd transportu cURL '.$errno.': '.curl_strerror($errno));
            if(in_array($code,array(301,302,303,307,308),TRUE)){$url=static::absolute($location,$url);continue;}
            if($asset){
                if($code!==200)throw new \RuntimeException('Upstream HTTP '.$code.($challenge?' (Cloudflare challenge)':''));
                $ext=strtolower(pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION));
                if(in_array($ext,array('js','mjs'),TRUE) && preg_match('~^(text|application)/(javascript|ecmascript)(?:;|$)~i',$type))$mime='text/javascript';
                elseif($ext==='css' && stripos($type,'text/css')===0)$mime='text/css';
                elseif($ext==='woff2' && substr($body,0,4)==='wOF2')$mime='font/woff2';
                elseif($ext==='glb' && strlen($body)>=12 && substr($body,0,4)==='glTF' && unpack('V',substr($body,4,4))[1]===2 && unpack('V',substr($body,8,4))[1]===strlen($body))$mime='model/gltf-binary';
                elseif($ext==='woff' && substr($body,0,4)==='wOFF')$mime='font/woff';
                elseif(in_array($ext,array('png','jpg','jpeg','webp','gif'),TRUE)){
                    $image=@getimagesizefromstring($body);$mime=$image['mime']??'';
                    if(!in_array($mime,array('image/png','image/jpeg','image/webp','image/gif'),TRUE))throw new \RuntimeException('Unsupported image');
                }
                else throw new \RuntimeException('Unsupported resource');
                return array('body'=>$body,'type'=>$mime,'url'=>$url);
            }
            if($code!==200 || !preg_match($scope===NULL?'~^(text/html|application/xhtml\+xml)\b~i':'~^(text/html|application/xhtml\+xml|application/json)\b~i',$type))throw new \RuntimeException('Odpowiedź źródła HTTP '.$code.'; typ '.substr(preg_replace('/[^a-z0-9\/+.\-]/i','',explode(';',$type)[0]),0,80).($challenge?'; Cloudflare challenge':'').'.');
            return array('body'=>$body,'type'=>stripos($type,'application/json')===0?'application/json':'text/html','url'=>$url);
        }
        throw new \RuntimeException('Zbyt wiele przekierowań.');
    }
    public static function fetch($url,$forum){$r=static::download($url,$forum);return static::sanitize($r['body'],$r['url']);}
    public static function sanitize($html,$url)
    {
        if(!mb_check_encoding($html,'UTF-8'))$html=mb_convert_encoding($html,'UTF-8','Windows-1250');
        $dom=new \DOMDocument('1.0','UTF-8');$old=libxml_use_internal_errors(TRUE);
        try{$dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);}finally{libxml_clear_errors();libxml_use_internal_errors($old);}
        $xpath=new \DOMXPath($dom);
        $js=(bool)\IPS\Settings::i()->sps_proxy_js;
        foreach($xpath->query(($js?'':'//script|').'//iframe|//frame|//frameset|//object|//embed|//applet|//base|//svg|//math|//template|//input[not(@type="checkbox" or @type="radio")]|//textarea|//select') as $node)$node->parentNode->removeChild($node);
        foreach($xpath->query('//form') as $node){while($node->firstChild)$node->parentNode->insertBefore($node->firstChild,$node);$node->parentNode->removeChild($node);}
        foreach($xpath->query('//*') as $node){
            if($node->nodeName==='meta' && ($node->hasAttribute('http-equiv') || !in_array(strtolower($node->getAttribute('name')),array('description','viewport','theme-color','keywords','author'),TRUE) && !($js && !$node->hasAttribute('name') && $node->hasAttribute('id') && $node->hasAttribute('data-src')))){$node->parentNode->removeChild($node);continue;}
            if($node->nodeName==='script'){
                if(strpos($node->getAttribute('src'),'/cloudflare-static/rocket-loader.min.js')!==FALSE){$node->parentNode->removeChild($node);continue;}
                if(preg_match('/^[a-f0-9]+-(text\/javascript|application\/javascript|module)$/iD',$node->getAttribute('type'),$match))$node->setAttribute('type',$match[1]);
                $node->removeAttribute('data-cfasync');
            }

            foreach(iterator_to_array($node->attributes) as $attr){$name=strtolower($attr->name);if((!$js&&strpos($name,'on')===0)||in_array($name,array('srcdoc','nonce','integrity','crossorigin','ping','action','formaction','autofocus','autoplay','is','xmlns'))) $node->removeAttributeNode($attr);}
            if($node->nodeName==='link' && strtolower($node->getAttribute('rel'))!=='stylesheet'){$node->parentNode->removeChild($node);continue;}
            foreach(array('href','src','poster','background','xlink:href') as $name){if(!$node->hasAttribute($name))continue;$value=$node->getAttribute($name);
                if($name==='src'&&preg_match('~^data:image/(png|jpeg|gif|webp);base64,[a-z0-9+/=\s]+$~i',$value))continue;
                if($name==='href'&&strpos($value,'#')===0)continue;
                $resolved=static::absolute($value,$url);if($resolved==='')$node->removeAttribute($name);else $node->setAttribute($name,$resolved);
            }
            // Prefer the ordinary src over untrusted responsive URL lists.
            $node->removeAttribute('srcset');
            if($node->nodeName==='script' && $node->hasAttribute('src')){
                $src=parse_url($node->getAttribute('src'));$host=strtolower($src['host']??'');
                if(($src['scheme']??'')!=='https'||isset($src['port'])||isset($src['user'])||$host===strtolower(parse_url(\IPS\Settings::i()->base_url,PHP_URL_HOST))||!Status::resolve($host)){$node->parentNode->removeChild($node);continue;}
            }
            if($node->nodeName==='a'){$node->setAttribute('target','_blank');$node->setAttribute('rel','noopener noreferrer nofollow ugc');}
            if($node->nodeName==='img' && !$node->hasAttribute('src')){foreach(array('data-src','data-original') as $attr)if($node->hasAttribute($attr)){$src=static::absolute($node->getAttribute($attr),$url);if($src)$node->setAttribute('src',$src);break;}}
        }
        $head=$dom->getElementsByTagName('head')->item(0);if(!$head){$head=$dom->createElement('head');$dom->documentElement->insertBefore($head,$dom->documentElement->firstChild);}
        $base=$dom->createElement('base');$base->setAttribute('href',$url);$head->appendChild($base);
        $meta=$dom->createElement('meta');$meta->setAttribute('charset','UTF-8');$head->insertBefore($meta,$head->firstChild);
        return preg_replace('~<script\b~i','<script data-cfasync="false"',$dom->saveHTML());
    }
    public static function cached($url,$forum,$key,$preview=FALSE)
    {
        $dir=sys_get_temp_dir().'/mpcservers_proxy_'.substr(hash('sha256',\IPS\ROOT_PATH),0,16);
        if(!is_dir($dir)&&!mkdir($dir,0700,TRUE)&&!is_dir($dir))throw new \RuntimeException('Cache jest niedostępny.');
        $path=$dir.'/'.hash('sha256',$key).'.json';$hash=hash('sha256','module-meta-v2:'.(int)\IPS\Settings::i()->sps_proxy_js.$url.json_encode(Presentation::forumConfig($forum)['hosts']));
        $cached=is_file($path)?json_decode(file_get_contents($path),TRUE):NULL;
        if($cached&&$cached['hash']===$hash&&$cached['expires']>time())return $cached['html'];
        if($preview&&$cached&&$cached['created']>time()-15)throw new \RuntimeException('Odczekaj 15 sekund przed kolejnym podglądem.');
        $lock=fopen($path.'.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);throw new \RuntimeException('Podgląd jest przygotowywany. Spróbuj ponownie za chwilę.');}
        try{
            $latest=is_file($path)?json_decode(file_get_contents($path),TRUE):NULL;
            if($latest && $latest['hash']===$hash && $latest['expires']>time())return $latest['html'];
            try{$html=static::fetch($url,$forum);$ttl=$preview?300:3600;}
            catch(\Throwable $e){try{\IPS\Log::log(array('host'=>parse_url($url,PHP_URL_HOST),'forum'=>(int)$forum,'stage'=>'fetch_or_sanitize','exception'=>get_class($e),'reason'=>substr($e->getMessage(),0,300)),'mpcservers_proxy_fetch');}catch(\Throwable $ignored){}$html='<!doctype html><meta charset="utf-8"><p style="font:16px sans-serif;padding:24px">Nie udało się pobrać podglądu. Otwórz pełną prezentację przyciskiem nad podglądem.</p>';$ttl=60;}
            $data=json_encode(array('hash'=>$hash,'created'=>time(),'expires'=>time()+$ttl,'html'=>$html));
            $tmp=$path.'.tmp';file_put_contents($tmp,$data,LOCK_EX);chmod($tmp,0600);rename($tmp,$path);return $html;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
    public static function cleanup()
    {
        $dir=sys_get_temp_dir().'/mpcservers_proxy_'.substr(hash('sha256',\IPS\ROOT_PATH),0,16);
        if(!is_dir($dir))return;
        foreach(new \DirectoryIterator($dir) as $file){
            if($file->isFile() && preg_match('/^[a-f0-9]{64}\.json$/D',$file->getFilename()) && $file->getMTime()<time()-7*86400) @unlink($file->getPathname());
        }
    }
}
