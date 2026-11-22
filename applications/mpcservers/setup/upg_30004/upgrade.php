<?php
namespace IPS\mpcservers\setup\upg_30004;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Upgrade
{
    public function step1()
    {
        $all=json_decode((string)\IPS\Settings::i()->sps_forum_options,TRUE) ?: array();
        foreach ($all as $id=>$cfg) {
            if (isset($cfg['category']) && \IPS\mpcservers\Servers::forum((int)$id)) { \IPS\mpcservers\Presentation::syncForumGame((int)$id); }
        }
        return TRUE;
    }
}
