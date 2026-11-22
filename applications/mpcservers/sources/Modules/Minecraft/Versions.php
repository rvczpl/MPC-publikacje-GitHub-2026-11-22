<?php
namespace IPS\mpcservers\Modules\Minecraft;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Registry;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Versions
{
    public static function parse($value): array
    {
        if(!is_string($value)||strlen($value)>5000)throw new \DomainException('Nieprawidłowa lista wersji Minecraft.');
        $out=array();
        foreach(preg_split('/[\s,;]+/',trim($value),-1,PREG_SPLIT_NO_EMPTY) as $version){
            if(!preg_match('/^[1-9][0-9]{0,2}\.(?:0|[1-9][0-9]{0,2})(?:\.(?:0|[1-9][0-9]{0,2}))?$/D',$version))throw new \DomainException('Podaj wersje numeryczne, np. 1.7, 1.7.10, 1.10.');
            $out[$version]=$version;
        }
        if(count($out)>200)throw new \DomainException('Możesz dodać najwyżej 200 wersji.');
        $out=array_values($out);usort($out,'version_compare');return $out;
    }
    public static function options($game): array
    {
        $all=json_decode((string)\IPS\Settings::i()->sps_minecraft_versions,TRUE)?:array();
        return $all[$game]??array_map(function($n){return '1.'.$n;},range(7,21));
    }
    public static function field($form,$game): void
    {
        if(Registry::kind($game)!=='minecraft')return;
        $f=new \IPS\Helpers\Form\TextArea('sps_mc_versions_'.$game,implode("\n",static::options($game)),TRUE,array('rows'=>8),function($v){if(!static::parse($v))throw new \DomainException('Dodaj co najmniej jedną wersję Minecraft.');});
        $f->label='Wersje Minecraft';$f->description='Jedna wersja w wierszu, np. 1.7.10. Autor publikacji musi wybrać co najmniej jedną. Wiele wyborów pokaże zakres od najniższej do najwyższej. Zapisane wybory pozostają po usunięciu wersji z tej listy.';$form->add($f);
    }
    public static function save($game,$values): void
    {
        if(!array_key_exists('sps_mc_versions_'.$game,$values))return;
        Registry::admin('minecraft');$all=json_decode((string)\IPS\Settings::i()->sps_minecraft_versions,TRUE)?:array();$all[$game]=static::parse($values['sps_mc_versions_'.$game]);
        \IPS\Settings::i()->changeValues(array('sps_minecraft_versions'=>json_encode($all)));
    }
    public static function stored($value): array
    {
        $list=is_string($value)?json_decode($value,TRUE):$value;
        if(!is_array($list))return array();
        try{return static::parse(implode("\n",array_filter($list,'is_string')));}catch(\DomainException $e){return array();}
    }
    public static function normalize($value,$game,$old=array()): array
    {
        if(!is_array($value)||count($value)>200)throw new \DomainException('Wybierz co najmniej jedną wersję Minecraft.');
        foreach($value as $v)if(!is_string($v))throw new \DomainException('Nieprawidłowa wersja Minecraft.');
        $chosen=static::parse(implode("\n",$value));
        if(!$chosen||array_diff($chosen,array_merge(static::options($game),$old)))throw new \DomainException('Wybierz co najmniej jedną wersję z dostępnej listy Minecraft.');
        return $chosen;
    }
    public static function label($value): string
    {
        $v=static::stored($value);return $v?$v[0].(count($v)>1?' - '.$v[count($v)-1]:''):'';
    }
    public static function topicLabel($topic): string
    {
        $row=Presentation::get($topic->tid);
        return $row && Registry::kind($row['category'])==='minecraft'?static::label($row['mc_versions']??''):'';
    }
    public static function html($v,$game): string
    {
        $selected=static::stored($v['mc_versions']??'');$options=static::parse(implode("\n",array_merge(static::options($game),$selected)));
        $e=function($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');};
        $out='<fieldset class="sps-field"><legend>Wersje Minecraft <span aria-hidden="true">*</span></legend><p class="i-font-size_-1">Wybierz jedną lub kilka wersji. Przy tytule pokażemy jedną wersję albo zakres.</p>';
        foreach($options as $version)$out.='<label style="display:inline-flex;align-items:center;gap:8px;margin:4px 12px 4px 0"><input style="width:auto;min-height:0" type="checkbox" name="sps_details[mc_versions][]" value="'.$e($version).'"'.(in_array($version,$selected,TRUE)?' checked':'').'>'.$e($version).'</label>';
        return $out.'</fieldset>';
    }
}
