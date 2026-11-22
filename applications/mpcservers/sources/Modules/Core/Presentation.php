<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\OrdinaryTopic;
use IPS\mpcservers\Modules\Core\TopicForm;
use IPS\mpcservers\Modules\Highlights\Recommended;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
use IPS\mpcservers\Modules\Community\CrewForm;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Presentation
{
    public static function forumConfig($forum)
    {
        $all=json_decode((string)\IPS\Settings::i()->sps_forum_options,TRUE) ?: array();
        $cats=static::categories();
        $defaults=array('tcp'=>0,'directory_topics'=>array(),'enabled'=>1,'category'=>isset($cats['other'])?'other':key($cats),'html'=>1,'hosts'=>(string)\IPS\Settings::i()->sps_embed_hosts);
        $cfg=array_merge($defaults,$all[(int)$forum]??array());
        if (!isset($cats[$cfg['category']])) { $cfg['category']=key($cats); }
        return $cfg;
    }
    public static function saveForumConfig($forum,$cfg)
    {
        $all=json_decode((string)\IPS\Settings::i()->sps_forum_options,TRUE) ?: array();
        $old=array_map('intval',$all[(int)$forum]['directory_topics']??array());
        $selected=array_values(array_unique(array_map('intval',$cfg['directory_topics']??array())));
        $cfg['directory_topics']=$selected;
        $all[(int)$forum]=$cfg;
        \IPS\Settings::i()->changeValues(array('sps_forum_options'=>json_encode($all)));
        foreach ($selected as $tid) { \IPS\Db::i()->update('mpcservers_presentations',array('is_server'=>0),array('tid=?',$tid)); }
        foreach (array_diff($old,$selected) as $tid) { \IPS\Db::i()->update('mpcservers_presentations',array('is_server'=>1),array('tid=?',$tid)); }
        static::syncForumGame($forum);
    }
    public static function syncForumGame($forum)
    {
        $cfg=static::forumConfig($forum); $profile=static::gameProfile($cfg);
        foreach (\IPS\Db::i()->select('p.*',array('mpcservers_presentations','p'),array('t.forum_id=?',(int)$forum))->join(array('forums_topics','t'),'t.tid=p.tid','INNER') as $row) {
            $values=array('category'=>$cfg['category']);
            if (!$profile['connection'] || !isset($profile['protocols'][$row['protocol']]) || $row['category']!==$cfg['category']) {
                $values+=array('protocol'=>'none','status'=>'unknown','checked'=>0,'players'=>0,'capacity'=>0);
            }
            if (!GameRewards::supports($cfg['category'])) { $values+=array('api_hash'=>'','api_owner'=>0); }
            // Retain saved endpoint data for administrators; hiding fields does not erase it.
            \IPS\Db::i()->update('mpcservers_presentations',$values,array('tid=?',(int)$row['tid']));
        }
    }
    public static function enabled($forum)
    {
        return Registry::gameEnabled(static::forumConfig($forum)['category']) && Servers::enabledForum((int)$forum) && !empty(static::forumConfig($forum)['enabled']);
    }
    public static function forumIds()
    {
        $ids=array(); foreach (Servers::forums() as $id=>$cfg) { if (static::enabled($id)) { $ids[]=(int)$id; } } return $ids;
    }
    public static function validateHosts($value)
    {
        foreach (array_filter(array_map('trim',preg_split('/\R/',(string)$value))) as $host) {
            if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/Di',$host)) { throw new \DomainException('Podaj same domeny, bez https:// i ścieżek.'); }
        }
    }
    public static function containsDirectoryTag($content)
    {
        $text=html_entity_decode(strip_tags((string)$content),ENT_QUOTES|ENT_HTML5,'UTF-8');
        return (bool)preg_match('/\[serwery(?:\s*=\s*[0-9,;\s]+)?\]/iu',$text);
    }
    public static function directoryIds()
    {
        $all=json_decode((string)\IPS\Settings::i()->sps_forum_options,TRUE) ?: array();
        $ids=array(); foreach ($all as $cfg) { foreach ((array)($cfg['directory_topics']??array()) as $id) { if ((int)$id>0) { $ids[]=(int)$id; } } }
        return array_values(array_unique($ids));
    }
    public static function isDirectory($topic)
    {
        return in_array((int)$topic->tid,static::directoryIds(),TRUE);
    }
    public static function categories()
    {
        $out=array();
        foreach (preg_split('/\R/',(string)\IPS\Settings::i()->sps_categories) as $line) {
            $pair=explode('=',trim($line),2);
            if (count($pair)===2 && preg_match('/^[a-z0-9_-]{1,40}$/D',$pair[0])) { $out[$pair[0]]=trim($pair[1]); }
        }
        return $out ?: array('minecraft'=>'Minecraft','cs'=>'Counter-Strike','metin'=>'Metin2','other'=>'Inne');
    }
    public static function get($tid)
    {
        try { $row=\IPS\Db::i()->select('*','mpcservers_presentations',array('tid=?',(int)$tid))->first();if(!Registry::gameFeature($row['category'],'recommended'))$row['featured']=0;return $row; }
        catch (\UnderflowException $e) { return NULL; }
    }
    public static function allowedUrl($url,$forum=NULL)
    {
        $p=parse_url($url);
        if (!$p || ($p['scheme']??'')!=='https' || empty($p['host']) || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && $p['port']!==443)) { return FALSE; }
        if (!\IPS\Settings::i()->sps_restrict_domains) return TRUE;
        return in_array(strtolower($p['host']),array_filter(array_map(function($v){return strtolower(trim($v));},preg_split('/\R/',$forum===NULL?(string)\IPS\Settings::i()->sps_embed_hosts:static::forumConfig($forum)['hosts']))),TRUE);
    }
    public static function fields($topic,$forum)
    {
        if ($topic && static::isDirectory($topic)) { return array(); }
        if (OrdinaryTopic::saved($topic) && !OrdinaryTopic::allowed($topic)) { return array(); }
        $fields=array('sps_details'=>TopicForm::field($topic,$forum));if(Registry::enabled('community'))$fields['sps_crew']=CrewForm::field($topic,$forum);return $fields;
    }
    public static function gameProfile($cfg)
    {
        return Registry::kind($cfg['category'])==='minecraft'?\IPS\mpcservers\Modules\Minecraft\Policy::profile($cfg):\IPS\mpcservers\Modules\Metin2\Policy::profile($cfg);
    }
    public static function connectionData($v,$cfg)
    {
        $profile=static::gameProfile($cfg);
        if (!$profile['connection']) { return array('host'=>'','port'=>0,'protocol'=>'none'); }
        $protocol=(string)($v['protocol']??'none');
        if (!isset($profile['protocols'][$protocol])) { throw new \DomainException('Wybrana metoda sprawdzania statusu nie pasuje do gry tego działu.'); }
        $host=strtolower(trim((string)($v['host']??'')));
        if ($host!=='' && !preg_match('/^(?=.{1,253}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D',$host)) { throw new \DomainException('sps_bad_host'); }
        $raw=$v['port']??$profile['port'];
        $port=($raw==='' && $protocol==='none')?0:filter_var($raw,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1,'max_range'=>65535)));
        if ($port===FALSE || ($protocol!=='none' && $host==='')) { throw new \DomainException('Podaj adres oraz port od 1 do 65535 dla sprawdzania statusu.'); }
        return array('host'=>$host,'port'=>(int)$port,'protocol'=>$protocol);
    }
    public static function connectionLabel($row,$forum)
    {
        if (!static::gameProfile(static::forumConfig($forum))['connection'] || $row['host']==='') { return ''; }
        return $row['host'].':'.(int)$row['port'];
    }
    public static function linkModeEnabled($cfg) { return Registry::kind($cfg['category'])==='metin2' && Registry::enabled('metin2') && (bool)\IPS\Settings::i()->sps_link_mode_enabled && !empty($cfg['html']); }
    public static function normalize($v,$forum=NULL,$tid=0)
    {
        if (!is_array($v)) { throw new \DomainException('Nieprawidłowe dane prezentacji.'); }
        foreach (array('is_server','category','mode','url','host','port','protocol','icon','open_date') as $key) {
            if (isset($v[$key]) && !is_scalar($v[$key])) { throw new \DomainException('Nieprawidłowe dane prezentacji.'); }
        }
        $cfg=static::forumConfig($forum);
        $mode=($v['mode']??'post')==='html'?'html':'post';
        $url=trim((string)($v['url']??''));
        if ($mode==='html' && !static::linkModeEnabled($cfg)) { throw new \DomainException('sps_link_mode_disabled'); }
        if ($mode==='html' && (!$cfg['html'] || !static::allowedUrl($url,$forum))) { throw new \DomainException('sps_bad_url'); }
        $category=$cfg['category'];
        $connection=static::connectionData($v,$cfg);
        $icon=trim((string)($v['icon']??''));
        if (strlen($icon)>2000 || ($icon!=='' && !static::allowedIcon($icon))) { throw new \DomainException('sps_bad_icon'); }
        if (strlen($url)>2000) { throw new \DomainException('sps_bad_url'); }
        $date=trim((string)($v['open_date']??''));
        if (!static::validDate($date)) { throw new \DomainException('sps_bad_date'); }
        $saved=!static::linkModeEnabled($cfg) && $tid ? static::get($tid) : NULL;
        $versions=NULL;
        if(Registry::kind($category)==='minecraft'){$previous=$tid?static::get($tid):NULL;$old=($previous&&$previous['category']===$category)?\IPS\mpcservers\Modules\Minecraft\Versions::stored($previous['mc_versions']??''):array();$versions=json_encode(\IPS\mpcservers\Modules\Minecraft\Versions::normalize($v['mc_versions']??array(),$category,$old));}
        return array('mc_versions'=>$versions,'is_server'=>1,'category'=>$category,'mode'=>$saved ? $saved['mode'] : $mode,'url'=>$saved ? $saved['url'] : ($mode==='html'?$url:''),'host'=>$connection['host'],'port'=>$connection['port'],'protocol'=>$connection['protocol'],'icon'=>$icon,'open_date'=>$date);
    }
    public static function validDate($value)
    {
        if ($value==='') { return TRUE; }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$value,$m)) { return FALSE; }
        return (int)$m[1]>=1970 && (int)$m[1]<=2199 && checkdate((int)$m[2],(int)$m[3],(int)$m[1]);
    }
    public static function opening($row)
    {
        $date=$row['open_date']??'';
        if ($date==='' || !static::validDate($date)) { return 'Data otwarcia niepodana'; }
        return 'Otwarcie: '.substr($date,8,2).'.'.substr($date,5,2).'.'.substr($date,0,4);
    }
    public static function allowedIcon($url)
    {
        $p=parse_url($url);
        return $p && ($p['scheme']??'')==='https' && !empty($p['host']) && !isset($p['user']) && !isset($p['pass']) && (!isset($p['port']) || $p['port']===443) && preg_match('/\.(png|jpe?g|gif|webp)$/i',$p['path']??'');
    }
    public static function iconHtml($r)
    {
        if (empty($r['icon']) || !static::allowedIcon($r['icon'])) { return ''; }
        return '<img src="'.htmlspecialchars($r['icon'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" alt="" width="72" height="72" loading="lazy" referrerpolicy="no-referrer" style="object-fit:contain;float:right;margin:0 0 12px 16px;border-radius:8px">';
    }
    public static function store($tid,$values)
    {
        $db=\IPS\Db::i(); $old=static::get($tid);
        if (static::isDirectory(\IPS\forums\Topic::load((int)$tid))) { $values['is_server']=0; }
        if ($old) {
            if ($old['host']!==$values['host'] || $old['port']!=$values['port'] || $old['protocol']!==$values['protocol']) { $values+=array('status'=>'unknown','checked'=>0,'players'=>0,'capacity'=>0); }
            $db->update('mpcservers_presentations',$values,array('tid=?',(int)$tid));
        } else { $db->insert('mpcservers_presentations',array_merge(array('tid'=>(int)$tid,'status'=>'unknown'),$values)); }
    }
    public static function topic($tid)
    {
        $t=\IPS\forums\Topic::load((int)$tid);
        if (!$t->canView() || $t->hidden() || !static::enabled($t->forum_id) || $t->state==='link' || static::isDirectory($t) || empty(static::get($tid)['is_server'])) { throw new \OutOfRangeException; }
        return $t;
    }
    /**
     * Topics for many entries in one query (Directory, owner panel).
     */
    public static function loadTopics(array $tids)
    {
        $out=array(); $tids=array_values(array_unique(array_filter(array_map('intval',$tids))));
        if (!$tids) { return $out; }
        foreach (\IPS\Db::i()->select('*','forums_topics',array(\IPS\Db::i()->in('tid',$tids))) as $row) { $out[(int)$row['tid']]=\IPS\forums\Topic::constructFromData($row); }
        return $out;
    }
    /**
     * Same rules as topic(), for a topic and entry already loaded.
     */
    public static function visible($t,$row)
    {
        return !empty($row['is_server']) && $t->state!=='link' && static::enabled($t->forum_id) && $t->canView() && !$t->hidden() && !static::isDirectory($t);
    }
    public static function url($params=array())
    {
        return \IPS\Http\Url::internal('app=mpcservers&module=directory&controller=browse','front')->setQueryString($params);
    }
    public static function status($r)
    {
        if ($r['protocol']==='none' || !$r['checked'] || time()-(int)$r['checked']>900) { return 'Status nieznany'; }
        if ($r['status']==='blocked') { return 'Adres niedozwolony'; }
        if ($r['status']!=='online') { return 'Brak odpowiedzi'; }
        return in_array($r['protocol'],array('minecraft','source'),TRUE) ? 'Online · '.(int)$r['players'].' / '.(int)$r['capacity'] : 'Port TCP dostępny';
    }
    public static function render($topic)
    {
        if (!static::enabled($topic->forum_id) || !$topic->canView() || $topic->hidden()) { return ''; }
        if (static::isDirectory($topic)) { return Directory::render(array((int)$topic->forum_id),(int)$topic->tid); }
        $r=static::get($topic->tid); if (!$r || empty($r['is_server'])) { return ''; }
        $esc=function($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
        $out='<section class="ipsBox i-padding_3">'.static::iconHtml($r).(!empty($r['featured'])?Recommended::badge():'').$esc(static::opening($r)).'<p><a href="'.$esc(static::url()).'">Lista serwerów</a> · '.$esc(static::status($r)).' · Podbicia: '.(int)$r['votes'].' <a class="ipsButton ipsButton--small ipsButton--primary" href="'.$esc(static::url(array('do'=>'vote','id'=>$topic->tid))).'">Podbij / odbierz kod</a></p>';
        if ($r['mode']==='html' && static::linkModeEnabled(static::forumConfig($topic->forum_id)) && static::allowedUrl($r['url'],$topic->forum_id)) {
            $out.='<p><a href="'.$esc($r['url']).'" target="_blank" rel="noopener noreferrer nofollow ugc">Otwórz pełną prezentację</a></p><iframe title="Prezentacja serwera" src="'.$esc(\IPS\mpcservers\Modules\Metin2\PresentationProxy::url($topic->tid)).'" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" loading="lazy" style="width:100%;height:75vh;min-height:420px;border:0"></iframe><p class="i-color_soft">Podgląd przez proxy. Pełna prezentacja jest dostępna z linku powyżej.</p>';
        }
        return $out.'</section>';
    }
}
