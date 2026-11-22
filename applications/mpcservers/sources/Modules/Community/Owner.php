<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Highlights\Recommended;
use IPS\mpcservers\Modules\Community\OwnerAccess;
use IPS\mpcservers\Modules\Community\OwnerStats;
use IPS\mpcservers\Modules\Minecraft\GameRewards;
use IPS\mpcservers\Modules\Lists\Boost;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Community\CrewLock;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Owner
{
    public static function url($params=array())
    {
        return \IPS\Http\Url::internal('app=mpcservers&module=owners&controller=panel','front')->setQueryString($params);
    }
    public static function endpoint()
    {
        return (string)\IPS\Http\Url::internal('app=mpcservers&module=owners&controller=rewards','front');
    }
    public static function owns($topic,$member) { return $topic && OwnerAccess::isOwner($topic->tid,$member->member_id); }
    public static function canIntegrate($topic,$member) { return static::owns($topic,$member); }
    public static function topic($tid,$member) { return OwnerAccess::requireTopic($tid,$member); }
    public static function servers($member)
    {
        if (!$member->member_id) { return array(); }
        $where=array(array('p.is_server=1'),array(\IPS\Db::i()->in('p.tid',OwnerAccess::ids($member->member_id)?:array(0))),array('t.approved=1'),array(\IPS\Db::i()->in('t.forum_id',Presentation::forumIds() ?: array(0))));
        $out=array(); $rows=array();
        foreach (\IPS\Db::i()->select('p.*',array('mpcservers_presentations','p'),$where,'p.category ASC,p.tid DESC')->join(array('forums_topics','t'),'t.tid=p.tid','INNER') as $row) { $rows[]=$row; }
        $topics=Presentation::loadTopics(array_map(function($r){return (int)$r['tid'];},$rows));
        foreach ($rows as $row) {
            $topic=$topics[(int)$row['tid']]??NULL;
            if ($topic && Presentation::visible($topic,$row) && static::owns($topic,$member)) { $out[]=array($row,$topic); }
        }
        return $out;
    }
    public static function rotate($tid,$member)
    {
        $publication=static::topic($tid,$member);
        $db=\IPS\Db::i(); $db->query('START TRANSACTION');
        try {
            $row=$db->query('SELECT * FROM `'.$db->prefix.'mpcservers_presentations` WHERE tid='.(int)$tid.' FOR UPDATE')->fetch_assoc();
            $topic=$db->query('SELECT starter_id,approved,forum_id,state FROM `'.$db->prefix.'forums_topics` WHERE tid='.(int)$tid.' FOR UPDATE')->fetch_assoc();
            if (!$row || !$topic || empty($row['is_server']) || !GameRewards::enabled($row['category'],$tid) || !static::canIntegrate($publication,$member) || (int)$topic['approved']!==1 || !Presentation::enabled($topic['forum_id']) || $topic['state']==='closed') { throw new \OutOfRangeException; }
            $key=bin2hex(random_bytes(32));
            $db->update('mpcservers_presentations',array('api_hash'=>hash('sha256',$key),'api_owner'=>(int)$member->member_id),array('tid=?',(int)$tid));
            $db->query('COMMIT'); return $key;
        } catch (\Throwable $e) { $db->query('ROLLBACK'); throw $e; }
    }
    public static function config($tid,$key)
    {
        $endpoint=str_replace("'","''",static::endpoint());
        return "# MPCforum - konfiguracja nagród dla tego serwera\nendpoint: '".$endpoint."'\nserver-id: ".(int)$tid."\napi-key: '".$key."'\nreward-command: 'give %player% minecraft:diamond 1'\ntrusted-proxy: false\n";
    }
    public static function panel()
    {
        $member=\IPS\Member::loggedIn();if(!$member->member_id)return '';
        $e=array(Directory::class,'esc');$servers=static::servers($member);$cats=Presentation::categories();$available=array();
        foreach($servers as list($row,$topic))$available[$row['category']]=($available[$row['category']]??0)+1;
        $game=is_scalar(\IPS\Request::i()->game)?(string)\IPS\Request::i()->game:'';if(!isset($available[$game]))$game='';
        $out='<section class="sps-owner-panel"><header class="sps-owner-header ipsBox i-padding_3"><div><span class="sps-owner-eyebrow">PANEL WŁAŚCICIELA</span><h1 class="ipsTitle ipsTitle--h2">Moje serwery <span class="sps-owner-count">'.count($servers).'</span></h1><p class="i-color_soft">Serwery, w których jesteś oznaczony jako właściciel.</p></div><nav class="sps-owner-navlinks" aria-label="Menu serwerów"><a class="ipsButton ipsButton--inherit ipsButton--small" href="'.$e(Presentation::url()).'"><i class="fa-solid fa-list" aria-hidden="true"></i> Lista serwerów</a>'.(\IPS\mpcservers\Modules\Core\Registry::enabled('highlights')?'<a class="ipsButton ipsButton--inherit ipsButton--small" href="'.$e(Rankings::url()).'"><i class="fa-solid fa-trophy" aria-hidden="true"></i> TOP serwery</a>':'').' </nav></header>';
        if(!$servers)return $out.'<div class="ipsBox i-padding_3 sps-owner-empty"><i class="fa-solid fa-server" aria-hidden="true"></i><h2>Twoje serwery pojawią się tutaj</h2><p>Dostęp uzyskasz po przypisaniu Twojego konta do roli Właściciel w ekipie serwera.</p><a class="ipsButton ipsButton--primary" href="'.$e(Presentation::url()).'">Przejdź do listy serwerów</a></div></section>'.static::panelCss();
        $out.='<nav class="sps-owner-tabs" aria-label="Filtruj moje serwery według gry"><a href="'.$e(static::url()).'"'.($game===''?' aria-current="page"':'').'>Wszystkie <span>'.count($servers).'</span></a>';
        foreach($cats as $id=>$label)if(isset($available[$id]))$out.='<a href="'.$e(static::url(array('game'=>$id))).'"'.($game===$id?' aria-current="page"':'').'>'.$e($label).' <span>'.$available[$id].'</span></a>';
        $out.='</nav><div class="sps-owner-grid">';$weekly=Rankings::counts(Rankings::period());
        foreach($servers as list($row,$topic)){
            if($game!==''&&$row['category']!==$game)continue;
            $icon=Presentation::iconHtml($row);$status=Presentation::status($row);
            $out.='<article class="sps-owner-card ipsBox"><header class="sps-owner-card-head"><div class="sps-owner-icon">'.($icon?:'<i class="fa-solid fa-server" aria-hidden="true"></i>').'</div><div class="sps-owner-heading"><span class="sps-owner-game">'.$e($cats[$row['category']]??$row['category']).'</span><h2><a href="'.$e($topic->url()).'">'.$e($topic->title).'</a></h2><div class="sps-owner-badges">'.Recommended::titleTag($topic).Servers::topicBadges($topic).'</div></div></header>';
            $out.='<dl class="sps-owner-stats"><div><dt>Status</dt><dd>'.$e($status).'</dd></div><div><dt>Otwarcie</dt><dd>'.$e($row['open_date']?str_replace('Otwarcie: ','',Presentation::opening($row)):'Nie podano').'</dd></div><div><dt>Podbicia w tygodniu</dt><dd>'.(int)($weekly[$topic->tid]['votes']??0).'</dd></div></dl>';
            $out.='<div class="sps-owner-actions"><a class="ipsButton ipsButton--primary ipsButton--small" href="'.$e($topic->url()).'"><i class="fa-solid fa-eye" aria-hidden="true"></i> Zobacz publikację</a>';
            if(OwnerStats::allowed($topic->tid))$out.='<a class="ipsButton ipsButton--inherit ipsButton--small" href="'.$e(OwnerStats::url($topic->tid)).'"><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Statystyki</a>';
            if($topic->canEdit())$out.='<a class="ipsButton ipsButton--inherit ipsButton--small" href="'.$e($topic->url()->setQueryString('do','edit')).'"><i class="fa-solid fa-pencil" aria-hidden="true"></i> Edytuj publikację</a>';
            if(Crew::canManage($topic))$out.='<a class="ipsButton ipsButton--inherit ipsButton--small" href="'.$e(Crew::url(array('do'=>'edit','tid'=>$topic->tid))).'"><i class="fa-solid fa-users" aria-hidden="true"></i> Ekipa serwera</a>';
            $out.='</div>';
            if(CrewLock::blocked($topic))$out.='<p class="sps-owner-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Skład ekipy jest zablokowany przez moderację.</p>';
            if (GameRewards::enabled($row['category'],$row['tid']) && static::canIntegrate($topic,$member)) {
                $ready=$row['api_hash'] && OwnerAccess::isOwner($topic->tid,$row['api_owner']);
                $out.='<details class="sps-owner-integration"><summary><i class="fa-solid fa-plug" aria-hidden="true"></i> Integracja Minecraft i nagrody <span>'.($ready?'Token aktywny':'Do skonfigurowania').'</span></summary><div class="sps-owner-integration-body"><p>Token: '.($ready?'aktywny - jego pełna wartość nie jest ponownie wyświetlana':'nieaktywny').'</p><p><a href="'.$e(Owner::url(array('do'=>'token','id'=>$row['tid']))).'">'.($ready?'Zresetuj token i pobierz nową konfigurację':'Wygeneruj token i konfigurację').'</a> · <a href="'.$e(Owner::url(array('do'=>'download','id'=>$row['tid']))).'">Pobierz MPCforum.jar</a></p>';
                $out.='<details><summary>Instrukcja wdrożenia - Minecraft Java</summary><p>Integracja nagród jest opcjonalna. Samo podbijanie nie wymaga pluginu ani tokenu. Administracja forum może wyłączyć nagrody w ACP.</p><ol><li>Pobierz MPCforum.jar i umieść w katalogu plugins serwera Paper/Spigot.</li><li>Wygeneruj token powyżej. Panel przygotuje config.yml z adresem tego forum i ID tego serwera.</li><li>Wgraj config.yml do plugins/MPCforum/config.yml. Zmień reward-command na wybraną komendę nagrody.</li><li>Zrestartuj serwer. Gracz podbija wpis na liście, podaje nick i wpisuje w grze /nagroda KOD.</li></ol><p>Plugin wymaga weryfikacji kont graczy (online-mode lub zabezpieczone proxy). Folia i Bedrock nie są obsługiwane.</p><p>Przy aktualizacji starego pluginu zachowaj config.yml i claims.yml. Niepewne odbiory po awarii wymagają sprawdzenia dziennika przez administratora serwera.</p><p>Adres połączenia z forum: <code>'.$e(Owner::endpoint()).'</code></p></details></div></details>';
            }
            $out.='</article>';
        }
        return $out.'</div></section>'.static::panelCss();
    }
    public static function panelCss(){return <<<'HTML'
<style>
.sps-owner-panel{min-width:0}.sps-owner-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;border-radius:10px}.sps-owner-eyebrow{display:block;font-size:11px;font-weight:700;letter-spacing:.1em;opacity:.65;margin-bottom:7px}.sps-owner-header h1{margin:0;display:flex;align-items:center;gap:12px}.sps-owner-header p{margin:10px 0 0}.sps-owner-count{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;font-size:14px;border-radius:8px;background:rgba(128,128,190,.15)}.sps-owner-navlinks{display:flex;gap:8px;flex-wrap:wrap}.sps-owner-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:22px 0}.sps-owner-tabs a{display:flex;align-items:center;gap:9px;padding:9px 15px;border:1px solid rgba(128,128,160,.25);border-radius:8px;color:inherit;text-decoration:none!important}.sps-owner-tabs a[aria-current]{background:rgba(113,116,235,.2);border-color:#8287de}.sps-owner-tabs span{opacity:.65;font-size:12px}.sps-owner-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,470px),1fr));gap:20px}.sps-owner-card{min-width:0;border:1px solid rgba(128,128,160,.22);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}.sps-owner-card-head{display:flex;gap:16px;padding:24px;align-items:center}.sps-owner-icon{display:flex;align-items:center;justify-content:center;flex:0 0 64px;width:64px;height:64px;border-radius:12px;background:rgba(128,128,190,.13);color:#949ae4;font-size:27px;overflow:hidden}.sps-owner-icon img{display:block!important;float:none!important;margin:0!important;width:100%!important;height:100%!important;object-fit:contain}.sps-owner-heading{min-width:0}.sps-owner-game{font-size:12px;opacity:.7}.sps-owner-heading h2{font-size:18px;line-height:1.4;margin:5px 0 8px;overflow-wrap:anywhere}.sps-owner-heading h2 a{color:inherit}.sps-owner-badges:empty{display:none}.sps-owner-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:0 24px;padding:18px 0;border-top:1px solid rgba(128,128,160,.18);border-bottom:1px solid rgba(128,128,160,.18)}.sps-owner-stats dt{font-size:12px;opacity:.65;margin-bottom:7px}.sps-owner-stats dd{margin:0;font-weight:600;overflow-wrap:anywhere}.sps-owner-actions{display:flex;flex-wrap:wrap;gap:8px;padding:20px 24px;margin-top:auto}.sps-owner-actions .ipsButton{white-space:normal;max-width:100%}.sps-owner-note{margin:0;padding:0 24px 20px;font-size:12px;opacity:.75}.sps-owner-integration{border-top:1px solid rgba(128,128,160,.2)}.sps-owner-integration>summary{padding:16px 24px;cursor:pointer;font-weight:600}.sps-owner-integration>summary span{display:block;font-weight:400;font-size:12px;margin-top:4px;opacity:.65}.sps-owner-integration-body{padding:0 24px 22px;line-height:1.7}.sps-owner-integration-body code{white-space:normal;overflow-wrap:anywhere}.sps-owner-empty{text-align:center;margin-top:20px;padding:50px 24px}.sps-owner-empty>.fa{font-size:36px;color:#949ae4}.sps-owner-tabs a:focus-visible,.sps-owner-integration summary:focus-visible{outline:2px solid #949ae4;outline-offset:3px}@media(max-width:600px){.sps-owner-header{padding:20px}.sps-owner-card-head{padding:18px;gap:12px}.sps-owner-icon{width:48px;height:48px;flex-basis:48px}.sps-owner-stats{margin:0 18px;gap:10px}.sps-owner-actions{padding:18px}.sps-owner-actions a{flex:1 1 145px}.sps-owner-integration>summary{padding:16px 18px}}
</style>
HTML;
    }
    /** Czy pokazać "Panel właściciela serwerów" (IPS5: menu konta) */
    public static function hasPanel(): bool
    {
        static $has=NULL;
        if ($has!==NULL) return $has;
        $has=FALSE;
        if (!\IPS\Member::loggedIn()->member_id) return $has;
        try {
            foreach (OwnerStats::linked() as $tid) {
                $topic=Presentation::topic($tid);$row=Presentation::get($tid);
                if ($topic && $row && Presentation::visible($topic,$row)) { return $has=TRUE; }
            }
        } catch (\Throwable $e) { Servers::logError($e); }
        return $has;
    }
    public static function nav()
    {
        if (!\IPS\Member::loggedIn()->member_id) return '';
        try {
            foreach (OwnerStats::linked() as $tid) {
                $topic=Presentation::topic($tid);$row=Presentation::get($tid);
                if ($topic && $row && Presentation::visible($topic,$row)) {
                    return '<li class="ipsMenu_item" data-menuItem="mpcServerOwner"><a href="'.Directory::esc(static::url()).'"><i class="fa-solid fa-server" aria-hidden="true"></i> Panel właściciela serwerów</a></li>';
                }
            }
        } catch (\Throwable $e) { Servers::logError($e); }
        return '';
    }
}
