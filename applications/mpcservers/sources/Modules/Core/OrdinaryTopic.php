<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Community\CrewLock;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class OrdinaryTopic
{
    public static function allowed($topic=NULL)
    {
        $member=\IPS\Member::loggedIn();
        if (CrewLock::blocked($topic)) { return FALSE; }
        if (!$member->member_id) { return FALSE; }
        if ($member->isAdmin() || Servers::inGroups($member,(string)\IPS\Settings::i()->sps_plain_groups)) { return TRUE; }
        return $topic && $topic->canEdit() && $topic::modPermission('edit',NULL,$topic->container());
    }
    public static function saved($topic)
    {
        if (!$topic || !$topic->tid) { return FALSE; }
        $row=Presentation::get($topic->tid);
        return $row && empty($row['is_server']);
    }
    public static function selected($topic=NULL)
    {
        if ($topic && Presentation::isDirectory($topic)) { return TRUE; }
        $kind=\IPS\Request::i()->sps_topic_kind;
        if (static::allowed($topic) && is_string($kind) && in_array($kind,array('ordinary','server'),TRUE)) { return $kind==='ordinary'; }
        return static::saved($topic);
    }
    public static function values($forum)
    {
        return array('is_server'=>0,'category'=>Presentation::forumConfig($forum)['category'],'mode'=>'post','url'=>'','host'=>'','port'=>0,'protocol'=>'none','status'=>'unknown','checked'=>0,'players'=>0,'capacity'=>0,'icon'=>'','open_date'=>'','api_hash'=>'','api_owner'=>0);
    }
    public static function selector($topic)
    {
        $plain=static::selected($topic);
        return '<label class="sps-field">Rodzaj tematu<select class="ipsInput ipsInput--select" name="sps_topic_kind" data-sps-kind><option value="server"'.(!$plain?' selected':'').'>Publikacja serwera</option><option value="ordinary"'.($plain?' selected':'').'>Zwykły temat - bez publikacji serwera</option></select></label><p class="i-color_soft">Zwykły temat nie wymaga danych serwera ani prefiksów i nie trafia na listę serwerów. Tematy ze spisem nadal wyznacza administrator w ACP.</p>'.static::script();
    }
    public static function script()
    {
        return <<<'HTML'
<style>.sps-topic-form[hidden], [data-mpcs-picker][hidden], .ipsFieldRow[hidden]{display:none!important}</style>
<script>
(function(){function init(){document.querySelectorAll('[data-sps-kind]').forEach(function(select){if(select.dataset.ready)return;select.dataset.ready='1';const form=select.closest('form');if(!form)return;function update(){const plain=select.value==='ordinary';form.querySelectorAll('.sps-topic-form, .sps-crew-editor, [data-mpcs-picker]').forEach(function(panel){panel.hidden=plain;panel.querySelectorAll('input,select,textarea,button').forEach(function(input){input.disabled=plain;});if(panel.matches('[data-mpcs-picker]')){const row=panel.closest('li.ipsFieldRow, .ipsFieldRow');if(row)row.hidden=plain;}});}select.addEventListener('change',update);update();});}if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{setTimeout(init,0);}if(window.jQuery){jQuery(document).on('contentChange',init);}})();
</script>
HTML;
    }
}
