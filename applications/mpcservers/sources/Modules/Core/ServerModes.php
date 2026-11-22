<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
/** Dedicated text editor for server modes, retaining existing prefix IDs and history. */
class ServerModes
{
    public static function isGroup(array $group): bool
    {
        return (bool)preg_match('/tryb|game.?mode|server.?mode/iu',(string)($group['name']??'')) || strcasecmp(trim((string)($group['name']??'')),'Easy')===0;
    }
    protected static function entries($game): array
    {
        $entries=array();
        foreach(Servers::forums() as $fid=>$cfg){
            if(Presentation::forumConfig($fid)['category']!==$game)continue;
            $groups=Servers::parseDefs($cfg['defs'])['groups'];$found=FALSE;
            foreach($groups as $key=>$group)if(static::isGroup($group)){$entries['sps_modes_'.$fid.'_'.$key]=array($fid,$key,$group);$found=TRUE;}
            if(!$found){$key=Servers::slug('Tryby serwera');$entries['sps_modes_'.$fid.'_'.$key]=array($fid,$key,array('name'=>'Tryby serwera','options'=>array(),'colors'=>array(),'multi'=>TRUE,'max'=>0));}
        }
        return $entries;
    }
    protected static function text(array $group): string
    {
        $out=array();foreach($group['options'] as $key=>$label)$out[]=$label.(!empty($group['colors'][$key])?' {'.$group['colors'][$key].'}':'');return implode("\n",$out);
    }
    public static function parse(string $value,string $name): array
    {
        if(strlen($value)>12000)throw new \DomainException('Lista trybów jest zbyt długa.');
        $lines=array_values(array_filter(array_map('trim',preg_split('/\R/u',$value)),fn($v)=>$v!==''));
        if(!$lines)return array();
        foreach($lines as $line)if(strpbrk($line,'|,:')!==FALSE)throw new \DomainException('Wpisz jeden tryb w wierszu, bez separatorów | , : .');
        $parsed=Servers::parseDefs($name.'+: '.implode(' | ',$lines));
        if($parsed['errors'])throw new \DomainException(implode(' ',$parsed['errors']));
        return reset($parsed['groups']);
    }
    public static function fields($form,$game): void
    {
        foreach(static::entries($game) as $field=>[$fid,$key,$group]){
            $f=new \IPS\Helpers\Form\TextArea($field,static::text($group),FALSE,array('rows'=>7),function($value)use($group){static::parse((string)$value,$group['name']);});
            $f->label='Tryby serwera - '.\IPS\forums\Forum::load($fid)->_title. ' / '.$group['name'];
            $f->description='Jeden tryb w wierszu. Autor publikacji wybiera jeden lub kilka; wybrane tryby rotują w jednym miejscu przed zwykłymi prefiksami. Opcjonalny kolor: Survival {#22aa55}. Zachowanie nazwy trybu zachowuje jego identyfikator. Puste pole nie usuwa istniejących trybów.';
            $form->add($f);
        }
    }
    public static function save($game,array $values): void
    {
        Registry::admin(Registry::kind($game));
        $pending=array();
        foreach(static::entries($game) as $field=>[$fid,$key,$group]){
            if(!array_key_exists($field,$values))continue;
            $replacement=static::parse((string)$values[$field],$group['name']);if(!$replacement)continue;
            if(!isset($pending[$fid]))$pending[$fid]=Servers::parseDefs(Servers::forums()[$fid]['defs'])['groups'];
            $pending[$fid][$key]=$replacement;
        }
        foreach($pending as $fid=>$groups){
            $lines=array();foreach($groups as $g){$suffix=$g['multi']?($g['max']?' (kilka, max '.$g['max'].')':'+'):'';$lines[]=$g['name'].$suffix.': '.str_replace("\n",' | ',static::text($g));}
            $defs=implode("\n",$lines);$parsed=Servers::parseDefs($defs);if($parsed['errors'])throw new \DomainException(implode(' ',$parsed['errors']));
            Servers::saveForum($fid,array('defs'=>$defs));
        }
    }
}
