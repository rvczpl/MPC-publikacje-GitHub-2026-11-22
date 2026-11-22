<?php
namespace IPS\mpcservers\Modules\Core;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class StatusMonitor
{
    public static function run(): mixed
    {
        $db=\IPS\Db::i();
        if (!\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('status') || !\IPS\Settings::i()->sps_status_enabled) { return NULL; }
        $ids=\IPS\mpcservers\Presentation::forumIds();
        $query=$db->select('p.*',array('mpcservers_presentations','p'),array(array('p.is_server=1'),array('p.checked<?',time()-300),array('t.approved=1'),array($db->in('t.forum_id',$ids ?: array(0)))),'p.checked ASC',20)->join(array('forums_topics','t'),'t.tid=p.tid','INNER');
        $deadline=microtime(TRUE)+20;
        foreach ($query as $r) {
            try {
                $topic=\IPS\forums\Topic::load($r['tid']);
                if (\IPS\mpcservers\Presentation::isDirectory($topic)) {
                    $db->update('mpcservers_presentations',array('is_server'=>0),array('tid=?',$r['tid']));
                    continue;
                }
                $profile=\IPS\mpcservers\Presentation::gameProfile(\IPS\mpcservers\Presentation::forumConfig($topic->forum_id));
                if (!$profile['connection'] || !isset($profile['protocols'][$r['protocol']])) {
                    $db->update('mpcservers_presentations',array('protocol'=>'none','status'=>'unknown','checked'=>time()),array('tid=?',$r['tid']));
                    continue;
                }
            } catch (\OutOfRangeException $e) { continue; }
            $result=\IPS\mpcservers\Status::probe($r);
            // A concurrent edit must not receive a result from the old endpoint.
            $db->update('mpcservers_presentations',$result,array('tid=? AND host=? AND port=? AND protocol=?',$r['tid'],$r['host'],$r['port'],$r['protocol']));
            if (microtime(TRUE)>$deadline) { break; }
        }
        return NULL;
    }
}
