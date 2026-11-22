<?php
namespace IPS\mpcservers\setup\upg_30002;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade
{
    public function step1()
    {
        $db=\IPS\Db::i();
        $schema=json_decode(file_get_contents(\IPS\ROOT_PATH.'/applications/mpcservers/data/schema.json'),TRUE);
        foreach (array('is_server','icon','featured','open_date','api_owner') as $name) {
            if (!$db->checkForColumn('mpcservers_presentations',$name)) { $db->addColumn('mpcservers_presentations',$schema['mpcservers_presentations']['columns'][$name]); }
        }
        // Automatic imports had no endpoint, HTML or explicit game category.
        // Preserve configured entries; ambiguous plain posts require explicit opt-in.
        $db->update('mpcservers_presentations',array('is_server'=>1),array("host<>? OR url<>? OR category<>?",'','','other'));
        $ids=array_filter(array_map('intval',explode(',',(string)\IPS\Settings::i()->sps_forums)));
        foreach ($ids as $id) {
            if (!\IPS\mpcservers\Servers::forum($id)) {
                try {
                    $forum=\IPS\forums\Forum::load($id);
                    if ($forum->sub_can_post && !$forum->redirect_on) { \IPS\mpcservers\Servers::saveForum($id,array('days'=>0)); }
                } catch (\OutOfRangeException $e) { /* A deleted forum is not recreated. */ }
            }
        }
        foreach ($db->select('p.tid,t.starter_id',array('mpcservers_presentations','p'),array('p.api_hash<>?',''))->join(array('forums_topics','t'),'t.tid=p.tid','INNER') as $row) {
            $db->update('mpcservers_presentations',array('api_owner'=>(int)$row['starter_id']),array('tid=?',(int)$row['tid']));
        }
        return TRUE;
    }
}
