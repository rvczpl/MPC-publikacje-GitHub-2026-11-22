<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class CrewLock
{
    public static function install()
    {
        $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
        if (!\IPS\Db::i()->checkForTable('mpcservers_crew_locks')) { \IPS\Db::i()->createTable($schema['mpcservers_crew_locks']); }
    }
    public static function get($tid,$current=FALSE)
    {
        if (!$tid) { return NULL; }
        if ($current) {
            return \IPS\Db::i()->query('SELECT * FROM `'.\IPS\Db::i()->prefix.'mpcservers_crew_locks` WHERE tid='.(int)$tid.' FOR UPDATE')->fetch_assoc() ?: NULL;
        }
        try { return \IPS\Db::i()->select('*','mpcservers_crew_locks',array('tid=?',(int)$tid))->first(); }
        catch (\UnderflowException $e) { return NULL; }
    }
    public static function canModerate($topic)
    {
        return $topic && $topic->tid && \IPS\Member::loggedIn()->member_id && $topic->canView()
            && $topic->canEdit() && $topic::modPermission('edit',NULL,$topic->container());
    }
    public static function blocked($topic,$current=FALSE)
    {
        return $topic && $topic->tid && !static::canModerate($topic) && !empty(static::get($topic->tid,$current)['locked']);
    }
    public static function set($topic,$locked,$reason='')
    {
        if (!static::canModerate($topic) || !Presentation::enabled($topic->forum_id) || Presentation::isDirectory($topic)) { throw new \DomainException('Brak uprawnień do blokowania ekipy.'); }
        $reason=trim($reason);
        if (mb_strlen($reason)>500) { throw new \DomainException('Uzasadnienie może mieć najwyżej 500 znaków.'); }
        $db=\IPS\Db::i();$db->query('START TRANSACTION');
        try {
            $db->query('SELECT tid FROM `'.$db->prefix.'forums_topics` WHERE tid='.(int)$topic->tid.' FOR UPDATE')->fetch_assoc();
            $before=static::get($topic->tid,TRUE);
            $after=array('tid'=>(int)$topic->tid,'locked'=>(int)(bool)$locked,'actor'=>(int)\IPS\Member::loggedIn()->member_id,'changed_at'=>time(),'reason'=>$reason);
            if ($before) { $db->update('mpcservers_crew_locks',$after,array('tid=?',(int)$topic->tid)); }
            else { $db->insert('mpcservers_crew_locks',$after); }
            $db->insert('mpcservers_crew_audit',array('tid'=>(int)$topic->tid,'actor'=>$after['actor'],'created_at'=>$after['changed_at'],
                'before_data'=>json_encode(array('action'=>'crew_lock','state'=>$before),JSON_UNESCAPED_UNICODE),
                'after_data'=>json_encode(array('action'=>'crew_lock','state'=>$after),JSON_UNESCAPED_UNICODE)));
            $db->query('COMMIT');
        } catch (\Throwable $e) { $db->query('ROLLBACK');throw $e; }
    }
    public static function notice($topic)
    {
        $row=static::get($topic->tid);
        if (empty($row['locked'])) { return ''; }
        return '<div class="ipsMessage ipsMessage--info">Ekipa serwera została ustalona i zablokowana przez moderację. Autor nie może zmienić osób ani ich ról.'
            .($row['reason']!==''?'<br>Uzasadnienie: '.Directory::esc($row['reason']):'').'</div>';
    }
}
