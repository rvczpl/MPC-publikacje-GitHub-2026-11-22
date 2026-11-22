<?php
namespace IPS\mpcservers\Modules\Metin2;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\Status;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ProxyAssets
{
    public static function url($url,$source,$token)
    {
        try{$url=ProxyAjax::target($url,array('source'=>'https://'.parse_url($source,PHP_URL_HOST).'/'));}catch(\Throwable $e){return $url;}
        if(!preg_match('~\.(css|woff2?|m?js|glb|png|jpe?g|webp|gif)$~i',parse_url($url,PHP_URL_PATH)))return $url;
        return (string)\IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxyajax','front')->setQueryString(array('asset'=>1,'cap'=>$token,'target'=>$url));
    }
    public static function css($css,$base,$token,$source)
    {
        return preg_replace_callback('~url\(\s*([\x22\x27]?)([^)\x22\x27]+)\1\s*\)~i',function($m)use($base,$token,$source){
            $value=trim($m[2]);if(stripos($value,'data:')===0||strpos($value,'#')===0)return $m[0];
            $url=PresentationProxy::absolute($value,$base);if(!$url)return 'url("")';
            // CSS backgrounds load without CORS. Only fonts need the signed proxy here.
            // WebGL textures are handled separately by the bridge, not by stylesheet rewriting.
            $target=preg_match('~\.woff2?$~i',(string)parse_url($url,PHP_URL_PATH))?static::url($url,$source,$token):$url;
            return 'url("'.str_replace(array('"',"\n","\r"),array('%22','',''),$target).'")';
        },$css);
    }
    /** Rewrite literal module imports; arbitrary dynamic expressions remain unsupported. */
    public static function javascript($js,$base,$token,$source)
    {
        // Module specifiers in this format contain no escaped quotes or newlines.
        $pattern='~(\bimport\s*\(\s*|\b(?:import|export)\s+(?:[^;\r\n]*?\s+from\s*)?)(["\x27])((?:\./|\.\./|/|https://)[^"\x27\\\\\r\n]+)\2~';
        return preg_replace_callback($pattern,function($m)use($base,$token,$source){
            $url=PresentationProxy::absolute($m[3],$base);
            $target=static::url($url,$source,$token);
            return $m[1].json_encode($target,JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        },$js);
    }
    public static function html($html,$source,$token)
    {
        // Inert boot metadata and static module scripts need CORS-capable URLs too.
        $html=preg_replace_callback('~<(?:meta|script)\b[^>]*>~i',function($m)use($source,$token){
            if(stripos($m[0],'data-src=')===FALSE && !preg_match('~type=["\x27]module["\x27]~i',$m[0]))return $m[0];
            return preg_replace_callback('~(data-src|src)="([^"]+)"~i',function($a)use($source,$token){
                $url=PresentationProxy::absolute(html_entity_decode($a[2],ENT_QUOTES|ENT_HTML5,'UTF-8'),$source);
                return $a[1].'="'.htmlspecialchars(static::url($url,$source,$token),ENT_QUOTES,'UTF-8').'"';
            },$m[0]);
        },$html);
        $html=preg_replace_callback('~<link\b[^>]*>~i',function($m)use($source,$token){
            return preg_replace_callback('~href="([^"]+)"~i',function($a)use($source,$token){
                $url=html_entity_decode($a[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
                return 'href="'.htmlspecialchars(static::url($url,$source,$token),ENT_QUOTES,'UTF-8').'"';
            },$m[0]);
        },$html);
        $html=preg_replace_callback('~<script\b[^>]*>~i',function($m){
            if(strpos($m[0],'controller=proxyajax')!==FALSE && preg_match('~type=["\x27]module["\x27]~i',$m[0]))return substr($m[0],0,-1).' crossorigin="use-credentials">';
            return $m[0];
        },$html);
        return preg_replace_callback('~<style\b([^>]*)>(.*?)</style>~is',function($m)use($source,$token){return '<style'.$m[1].'>'.static::css($m[2],$source,$token,$source).'</style>';},$html);
    }
}
