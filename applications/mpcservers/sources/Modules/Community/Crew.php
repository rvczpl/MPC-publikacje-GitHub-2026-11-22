<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Community\CrewLock;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Crew
{
    protected static $rosters=array();
    public static function install()
    {
        $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
        foreach(array('mpcservers_people','mpcservers_crew','mpcservers_crew_audit') as $table) {
            if (!\IPS\Db::i()->checkForTable($table)) { \IPS\Db::i()->createTable($schema[$table]); }
        }
    }
    public static function clearCache(){static::$rosters=array();}
    public static function url($params=array()) { return \IPS\Http\Url::internal('app=mpcservers&module=people&controller=browse','front')->setQueryString($params); }
    public static function roles($game)
    {
        $base=array('owner'=>'Właściciel','technician'=>'Technik');
        $defaults=array('minecraft'=>array('developer'=>'Programista','builder'=>'Budowniczy','admin'=>'Administrator gry','sponsor'=>'Sponsor'), 'cs'=>array('developer'=>'Programista pluginów','admin'=>'Administrator gry','sponsor'=>'Sponsor'), 'metin'=>array('developer'=>'Programista','mapper'=>'Mapper','support'=>'Wsparcie graczy','sponsor'=>'Sponsor'));
        $config=json_decode((string)\IPS\Settings::i()->sps_crew_roles,TRUE) ?: array();
        $extra=$config[$game]??($defaults[$game]??array('team'=>'Ekipa','sponsor'=>'Sponsor'));
        foreach ((array)$extra as $key=>$label) { if (preg_match('/^[a-z0-9_]{1,40}$/D',$key) && is_string($label) && trim($label)!=='') { $base[$key]=mb_substr(trim($label),0,120); } }
        return $base;
    }
    public static function canManage($topic,$forum=NULL)
    {
        if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community'))return FALSE;
        if (!\IPS\Member::loggedIn()->member_id) { return FALSE; }
        if (!$topic || !$topic->tid) { return $forum && \IPS\forums\Forum::load((int)$forum)->can('add'); }
        if (CrewLock::blocked($topic)) { return FALSE; }
        return Presentation::enabled($topic->forum_id) && !Presentation::isDirectory($topic) && $topic->canView() && ($topic->canEdit() || (int)$topic->starter_id===(int)\IPS\Member::loggedIn()->member_id);
    }
    public static function roster($tid)
    {
        if (!isset(static::$rosters[$tid])) {
            static::$rosters[$tid]=iterator_to_array(\IPS\Db::i()->select('c.*,p.name,p.member_id',array('mpcservers_crew','c'),array('c.tid=? AND c.ended_at=0',(int)$tid),'c.id ASC')->join(array('mpcservers_people','p'),'p.id=c.person_id','INNER'));
        }
        return static::$rosters[$tid];
    }
    public static function person($id)
    {
        try { for($i=0;$i<30;$i++){ $row=\IPS\Db::i()->select('*','mpcservers_people',array('id=?',(int)$id))->first();if(empty($row['merged_into']))return $row;$id=(int)$row['merged_into']; } throw new \DomainException('Nieprawidłowe powiązanie osoby.'); } catch (\UnderflowException $e) { throw new \DomainException('Nie znaleziono osoby w rejestrze.'); }
    }
    public static function personVisible($id)
    {
        $p=static::person($id);
        if ((int)$p['created_by']===(int)\IPS\Member::loggedIn()->member_id && \IPS\Member::loggedIn()->member_id) { return TRUE; }
        return (bool)static::records((int)$id);
    }
    public static function resolve($identity)
    {
        $identity=trim($identity);
        if (preg_match('/^#([1-9][0-9]*)$/D',$identity,$m)) {
            if (!static::personVisible((int)$m[1])) { throw new \DomainException('Ta osoba nie jest dostępna w rejestrze.'); }
            $p=static::person((int)$m[1]);return array('person_id'=>(int)$p['id'],'member_id'=>(int)$p['member_id'],'name'=>$p['name']);
        }
        if (substr($identity,0,1)==='@') {
            $member=\IPS\Member::load(substr($identity,1),'name');
            if (!$member->member_id) { throw new \DomainException('Nie znaleziono konta '.$identity.'. Wpisz dokładny nick po znaku @.'); }
            return array('person_id'=>0,'member_id'=>(int)$member->member_id,'name'=>$member->name);
        }
        if ($identity==='' || mb_strlen($identity)>120 || preg_match('/[\x00-\x1f<>]/u',$identity)) { throw new \DomainException('Podaj nazwę osoby (do 120 znaków), @nick konta lub #ID osoby z rejestru.'); }
        return array('person_id'=>0,'member_id'=>0,'name'=>$identity);
    }
    public static function normalize($value,$topic,$forum)
    {
        if (!static::canManage($topic,$forum)) { throw new \DomainException('Nie masz uprawnień do zmiany ekipy tego serwera.'); }
        if (!is_array($value) || count($value)>42) { throw new \DomainException('Ekipa może zawierać najwyżej 40 wpisów.'); }
        $roles=static::roles(Presentation::forumConfig($forum)['category']);$out=array();$seen=array();
        foreach($value as $key=>$row) {
            if ($key==='__sent') { continue; }
            if (!is_array($row)) { throw new \DomainException('Nieprawidłowe dane ekipy.'); }
            foreach(array('identity','role','since') as $name) { if (isset($row[$name]) && !is_scalar($row[$name])) { throw new \DomainException('Nieprawidłowe dane osoby.'); } }
            if(array_key_exists('member',$row) || array_key_exists('external',$row)) {
                $member=$row['member']??'';
                if(is_array($member)){ $member=array_values(array_filter($member,function($v){return $v!=='';}));if(count($member)>1)throw new \DomainException('Wybierz jedno konto na osobę.');$member=$member[0]??''; }
                if(!is_scalar($member) || !is_scalar($row['external']??'') || !is_scalar($row['person_id']??0))throw new \DomainException('Nieprawidłowe dane osoby.');
                $member=trim(html_entity_decode((string)$member,ENT_QUOTES,'UTF-8'));$external=trim((string)($row['external']??''));
                if(strpos($member,"\n")!==FALSE)throw new \DomainException('Wybierz jedno konto na osobę.');
                if($member!=='' && $external!=='')throw new \DomainException('Wybierz konto forum albo wpisz osobę bez konta - nie wypełniaj obu pól jednocześnie.');
                if($member!==''){$identity='@'.$member;}
                else {
                    $identity=$external;
                    if($external!=='' && !empty($row['person_id'])){
                        $existing=static::person((int)$row['person_id']);
                        if(!$existing['member_id'] && $existing['name']===$external){$identity='#'.(int)$existing['id'];}
                    }
                }
            } else {$identity=trim((string)($row['identity']??''));}
            if ($identity==='') { continue; }
            $role=(string)($row['role']??'');if (!isset($roles[$role])) { throw new \DomainException('Ta rola nie jest dostępna dla gry wybranej w ACP.'); }
            $since=trim((string)($row['since']??''));if (!Presentation::validDate($since) || ($since!=='' && $since>gmdate('Y-m-d'))) { throw new \DomainException('Podaj poprawną datę rozpoczęcia, nie późniejszą niż dzisiaj.'); }
            $p=static::resolve($identity);$dedupe=($p['member_id']?'m:'.$p['member_id']:($p['person_id']?'p:'.$p['person_id']:'x:'.mb_strtolower($p['name']))).':'.$role;
            if(isset($seen[$dedupe])) { throw new \DomainException('Ta osoba ma już przypisaną tę rolę.'); }$seen[$dedupe]=TRUE;
            $out[]=$p+array('role'=>$role,'role_label'=>$roles[$role],'since_date'=>$since);
        }
        if(count($out)>40)throw new \DomainException('Ekipa może zawierać najwyżej 40 wpisów.');
        return $out;
    }
    public static function sync($topic,array $entries)
    {
        if (!static::canManage($topic,$topic->forum_id)) { throw new \DomainException('Brak uprawnień do zmiany ekipy.'); }
        $db=\IPS\Db::i();$tid=(int)$topic->tid;$actor=(int)\IPS\Member::loggedIn()->member_id;$now=time();
        $db->query('START TRANSACTION');
        try {
            $db->query('SELECT tid FROM `'.$db->prefix.'forums_topics` WHERE tid='.$tid.' FOR UPDATE')->fetch_assoc();
            if (CrewLock::blocked($topic,TRUE)) { throw new \DomainException('Ekipa została zablokowana przez moderację.'); }
            unset(static::$rosters[$tid]);$before=static::roster($tid);$keep=array();$after=array();$externals=array();
            $meta=Presentation::get($tid);if (!$meta || empty($meta['is_server'])) { $entries=array(); }
            $roles=static::roles(Presentation::forumConfig($topic->forum_id)['category']);
            foreach($entries as $entry) {
                if(!isset($roles[$entry['role']]))throw new \DomainException('Konfiguracja ról zmieniła się. Odśwież formularz.');
                $person=(int)$entry['person_id'];if($person){$person=(int)static::person($person)['id'];}
                if(!$person && $entry['member_id']) {
                    $identity='member:'.(int)$entry['member_id'];
                    $db->insert('mpcservers_people',array('identity_key'=>$identity,'member_id'=>(int)$entry['member_id'],'name'=>$entry['name'],'created_by'=>$actor,'created_at'=>$now),FALSE,TRUE);
                    $person=(int)$db->select('id','mpcservers_people',array('identity_key=?',$identity))->first();
                } elseif(!$person) {
                    $key=mb_strtolower($entry['name']);
                    if(isset($externals[$key])){$person=$externals[$key];}else{$person=(int)$db->insert('mpcservers_people',array('identity_key'=>'external:'.bin2hex(random_bytes(16)),'name'=>$entry['name'],'created_by'=>$actor,'created_at'=>$now));$externals[$key]=$person;}
                }
                $match=NULL;
                foreach($before as $old) { if((int)$old['person_id']===$person && $old['role']===$entry['role'] && $old['since_date']===$entry['since_date'] && $old['game']===$meta['category']) {$match=(int)$old['id'];break;} }
                if($match) { $keep[]=$match; } else {
                    $keep[]=(int)$db->insert('mpcservers_crew',array('tid'=>$tid,'person_id'=>$person,'game'=>$meta['category'],'role'=>$entry['role'],'role_label'=>$roles[$entry['role']],'since_date'=>$entry['since_date'],'created_at'=>$now,'created_by'=>$actor));
                }
                $after[]=array('person_id'=>$person,'role'=>$entry['role'],'since_date'=>$entry['since_date']);
            }
            foreach($before as $old) { if(!in_array((int)$old['id'],$keep,TRUE)) { $db->update('mpcservers_crew',array('ended_at'=>$now,'ended_by'=>$actor),array('id=?',(int)$old['id'])); } }
            $simple=array_map(function($r){return array('person_id'=>(int)$r['person_id'],'role'=>$r['role'],'since_date'=>$r['since_date']);},$before);
            if($simple!==$after) { $db->insert('mpcservers_crew_audit',array('tid'=>$tid,'actor'=>$actor,'created_at'=>$now,'before_data'=>json_encode($simple),'after_data'=>json_encode($after))); }
            $db->query('COMMIT');unset(static::$rosters[$tid]);
        } catch(\Throwable $e) { $db->query('ROLLBACK');unset(static::$rosters[$tid]);throw $e; }
    }
    public static function records($person=NULL,$game='',$role='',$current=FALSE)
    {
        if($person){try{$person=(int)static::person($person)['id'];}catch(\DomainException $e){return array();}}
        $where=array();if($person) {$where[]=array('c.person_id=?',(int)$person);}if($game!==''){$where[]=array('c.game=?',$game);}if($role!==''){$where[]=array('c.role=?',$role);}if($current){$where[]=array('c.ended_at=0');}
        $out=array();$batch=array();
        foreach(\IPS\Db::i()->select('c.*,p.name,p.member_id',array('mpcservers_crew','c'),$where,'c.created_at DESC,c.id DESC')->join(array('mpcservers_people','p'),'p.id=c.person_id','INNER') as $row) {
            $batch[]=$row;if(count($batch)===100){$out=array_merge($out,static::visibleBatch($batch));$batch=array();}
        }
        return array_merge($out,static::visibleBatch($batch));
    }
    protected static function visibleBatch($rows)
    {
        $topics=Presentation::loadTopics(array_column($rows,'tid'));$out=array();
        foreach($rows as $r){$t=$topics[$r['tid']]??NULL;if(!$t || !$t->canView() || $t->hidden() || $t->state==='link' || Presentation::isDirectory($t)){continue;}$r['topic']=$t;$out[]=$r;}return $out;
    }
    public static function personLink($row)
    {
        return '<a href="'.Directory::esc(static::url(array('id'=>(int)$row['person_id']))).'">'.Directory::esc($row['name']).'</a>';
    }
    public static function roleHtml($topic,$role)
    {
        $out=array();foreach(static::roster($topic->tid) as $r){if($r['role']===$role){$out[]=static::personLink($r);}}return $out?implode('<br>',$out):'<span class="i-color_soft">Nie wskazano</span>';
    }
}
