<?php
namespace IPS\mpcservers\Modules\Lists;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\Media;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class ModeFilters
{
    public static function activeGames($forums)
    {
        $used=array();foreach($forums as $id){try{$forum=\IPS\forums\Forum::load((int)$id);if(Presentation::enabled($id) && $forum->can('view'))$used[Presentation::forumConfig($id)['category']]=TRUE;}catch(\OutOfRangeException $e){}}
        return array_intersect_key(Presentation::categories(),$used);
    }
    public static function gameIcon($game)
    {
        $icons=json_decode((string)\IPS\Settings::i()->sps_game_icons,TRUE)?:array();$url=Media::url($icons[$game]??'');
        if($url && Presentation::allowedIcon($url))return '<span class="sps-game-icon" aria-hidden="true"><img src="'.Directory::esc($url).'" alt="" loading="lazy" referrerpolicy="no-referrer"></span>';
        $art=static::gameArt($game);$icon=array('minecraft'=>'cube','cs'=>'crosshairs','medium'=>'shield','other'=>'gamepad')[$art]??'gamepad';
        return '<span class="sps-game-icon" aria-hidden="true"><i class="fa fa-'.$icon.'"></i></span>';
    }
    public static function gameIconField($form,$game,$label)
    {
        $icons=json_decode((string)\IPS\Settings::i()->sps_game_icons,TRUE)?:array();$id='sps_game_icon_'.$game;
        $f=Media::field($id,$icons[$game]??'',$id);
        $f->label='Tło gry';$f->description='Wgraj tło całego kafelka. Zalecane 1024 × 1024 px, PNG, JPG lub WebP, maksymalnie 5 MB. Grafika jest kadrowana do rozmiaru kafelka; ważne elementy umieść w centrum, bez napisów. Ustawienie wspólne dla działów tej gry.';$form->add($f);
    }
    public static function saveGameIcon($game,$values)
    {
        $icons=json_decode((string)\IPS\Settings::i()->sps_game_icons,TRUE)?:array();$url=Media::value($values['sps_game_icon_'.$game]??NULL,$icons[$game]??'');
        if($url==='')unset($icons[$game]);else $icons[$game]=$url;
        \IPS\Settings::i()->changeValues(array('sps_game_icons'=>json_encode($icons,JSON_UNESCAPED_UNICODE)));
    }
    public static function asset($name)
    {
        return rtrim((string)\IPS\Settings::i()->base_url,'/').'/applications/mpcservers/interface/tiles/'.$name.'.jpg';
    }
    public static function gameArt($game)
    {
        $game=strtolower((string)$game);
        if (strpos($game,'minecraft')!==FALSE) { return 'minecraft'; }
        if ($game==='cs' || strpos($game,'counter')!==FALSE || strpos($game,'source')!==FALSE) { return 'cs'; }
        if (strpos($game,'metin')!==FALSE) { return 'medium'; }
        return 'other';
    }
    public static function art($name,$game)
    {
        $key=strtolower(str_replace(' ','',(string)$name));
        $parts=explode('/',$key);
        if (static::gameArt($game)==='other' && preg_match('/skyblock|survival|oneblock|creative|vanilla|easyhc|anarchia|megadrop|boxpvp/',$key)) { return array(static::asset('minecraft')); }
        $known=array('easy','medium','hard');
        if (count($parts)<=2 && !array_diff($parts,$known)) {
            return array_map(function($part) use($game){return static::asset(in_array(static::gameArt($game),array('minecraft','cs'),TRUE)?static::gameArt($game):$part);},$parts);
        }
        return array(static::asset(static::gameArt($game)));
    }
    protected static function option($field,$value,$label,$selected,$urls,$accent,$icon='')
    {
        $e=array('IPS\\mpcservers\\Directory','esc');
        $out='<label class="sps-mode-option" style="--sps-tile-accent:'.$e($accent).'"><input type="radio" name="'.$e($field).'" value="'.$e($value).'"'.((string)$selected===(string)$value?' checked':'').'><span class="sps-mode-badge sps-mode-graphic"><span class="sps-tile-art" aria-hidden="true">';
        foreach ($urls as $url) { $out.='<img src="'.$e($url).'" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer">'; }
        return $out.'</span>'.$icon.'<span class="sps-tile-caption'.(mb_strlen($label)>12?' sps-tile-caption-long':'').'">'.str_replace('/','/<wbr>',$e($label)).'</span><span class="sps-tile-check" aria-hidden="true">✓</span></span></label>';
    }
    protected static function reset($field,$selected,$label)
    {
        $e=array('IPS\\mpcservers\\Directory','esc');
        return '<label class="sps-tile-reset"><input type="radio" name="'.$e($field).'" value=""'.($selected===''?' checked':'').'><span>'.$e($label).'</span></label>';
    }
    public static function games($categories,$selected,$field='category')
    {
        $custom=json_decode((string)\IPS\Settings::i()->sps_game_images,TRUE) ?: array();
        $out='<fieldset class="sps-mode-group sps-game-filters"><legend>Wybierz grę</legend><div class="sps-mode-options">'.static::option($field,'','Wszystkie gry',$selected,array(static::asset('other')),'#738bff');
        foreach($categories as $key=>$label) {
            $backgrounds=json_decode((string)\IPS\Settings::i()->sps_game_icons,TRUE)?:array();
            $url=Media::url($backgrounds[$key]??($custom[$key]??''));
            if (!$url || !Presentation::allowedIcon($url)) { $url=static::asset(static::gameArt($key)); }
            $out.=static::option($field,$key,$label,$selected,array($url),'#5b8cff');
        }
        return $out.'</div></fieldset>';
    }
    public static function render($groups,$selected,$forums)
    {
        if (!$groups) { return ''; }
        $e=array('IPS\\mpcservers\\Directory','esc');$images=array();$game='other';
        foreach ($forums as $forum) { $cfg=Presentation::forumConfig($forum);$game=$cfg['category'];foreach ((array)($cfg['mode_images']??array()) as $group=>$options) { foreach ((array)$options as $option=>$url) { if (!isset($images[$group][$option])) { $images[$group][$option]=Media::url($url); } } } }
        $out='<div class="sps-mode-filters">';
        foreach ($groups as $key=>$group) {
            $label=strcasecmp(trim($group['name']),'Easy')===0?'Tryb serwera':$group['name'];
            $compact=count($groups)>1 || count($group['options'])>8;
            $chosen=(string)($selected[$key]??'');$chosenLabel=$group['options'][$chosen]??'Wszystkie';
            if($compact)$out.='<details data-sps-filter-group="'.$e($key).'" class="sps-filter-section'.($chosen!==''?' sps-filter-selected':'').'"><summary><span class="sps-filter-heading">'.$e($label).'<small>'.count($group['options']).' opcji</small></span><span class="sps-filter-current">'.$e($chosenLabel).'</span></summary>';
            $out.='<fieldset class="sps-mode-group'.($compact?' sps-mode-compact':'').'"><legend'.($compact?' class="sps-filter-legend"':'').'>'.$e($label).'</legend><div class="sps-mode-options">'.static::option('prefix['.$key.']','','Wszystkie tryby',$selected[$key]??'',!empty($images[$key]['__all']) && Presentation::allowedIcon($images[$key]['__all'])?array($images[$key]['__all']):(static::gameArt($game)==='medium'?array(static::asset('easy'),static::asset('medium'),static::asset('hard')):array(static::asset(static::gameArt($game)))),'#738bff');
            foreach ($group['options'] as $value=>$name) {
                $url=$images[$key][$value]??'';
                $urls=$url && Presentation::allowedIcon($url)?array($url):static::art($name,$game);
                $accent=stripos($name,'hard')!==FALSE?'#fb407e':(stripos($name,'easy')!==FALSE?'#19d9da':'#9954ff');
                $out.=static::option('prefix['.$key.']',$value,$name,$selected[$key]??'',$urls,$accent);
            }
            $out.='</div></fieldset>'.($compact?'</details>':'');
        }
        return $out.'</div>';
    }
    public static function gameImageFields($form)
    {
        $current=json_decode((string)\IPS\Settings::i()->sps_game_images,TRUE) ?: array();$fields=array();
        foreach(Presentation::categories() as $key=>$label) {
            $field='sps_game_image_'.substr(hash('sha256',$key),0,20);$fields[$field]=$key;
            $input=Media::field($field,$current[$key]??'');
            $input->label='Tło gry: '.$label;$input->description='Wgraj własną grafikę lub pozostaw wbudowaną ilustrację. Maksymalnie 5 MB. Zalecany wymiar: 1024 × 1024 px (proporcje 1:1). Grafika jest automatycznie skalowana i przycinana do kafelka, którego szerokość zależy od liczby przycisków i ekranu. Ważne elementy umieść w centrum, bez napisów - nazwę dodaje system.';$form->add($input);
        }
        return $fields;
    }
    public static function gameImagesFromForm($fields,$values)
    {
        $current=json_decode((string)\IPS\Settings::i()->sps_game_images,TRUE)?:array();$out=array();foreach($fields as $field=>$key){$value=Media::value($values[$field]??NULL,$current[$key]??'');if($value!=='')$out[$key]=$value;}return $out;
    }
    public static function imageFields($form,$groups,$config)
    {
        $fields=array();
        if (!$groups) { return $fields; }
        $form->addMessage('Grafiki trybów są opcjonalne. Bez własnej grafiki wyświetlana jest wbudowana ilustracja. Zalecany wymiar: 1024 × 1024 px (proporcje 1:1). Grafika jest automatycznie skalowana i przycinana do kafelka, którego szerokość zależy od liczby przycisków i ekranu. Ważne elementy umieść w centrum, bez napisów - nazwę dodaje system. Po dodaniu nowych trybów zapisz ustawienia i otwórz edycję ponownie, aby przypisać im grafiki.');
        foreach ($groups as $key=>$group) { foreach (array('__all'=>'Wszystkie tryby')+$group['options'] as $option=>$name) {
            $field='sps_mode_image_'.substr(hash('sha256',$key.':'.$option),0,20);
            $fields[$field]=array($key,$option,$config['mode_images'][$key][$option]??'');
            $input=Media::field($field,$config['mode_images'][$key][$option]??'','sps_mode_upload_'.substr(hash('sha256',$key.':'.$option),0,20));
            $input->label='Grafika: '.$name;
            $input->description='1024 × 1024 px (1:1), maks. 5 MB. PNG, JPG, GIF lub WebP. Nazwę dodaje system.';
            $form->add($input);
        } }
        return $fields;
    }
    public static function imagesFromForm($fields,$values)
    {
        $images=array();foreach($fields as $field=>$keys){$url=Media::value($values[$field]??NULL,$keys[2]??'');if($url!=='')$images[$keys[0]][$keys[1]]=$url;}return $images;
    }
}
