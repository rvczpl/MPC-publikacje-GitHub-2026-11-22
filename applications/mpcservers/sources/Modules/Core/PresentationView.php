<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\OrdinaryTopic;
use IPS\mpcservers\Modules\Highlights\Recommended;
use IPS\mpcservers\Modules\Community\OwnerAccess;
use IPS\mpcservers\Modules\Lists\Boost;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Highlights\Awards;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Community\CrewLock;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class PresentationView
{
    public static function editLink($topic,$comment)
    {
        $e=array('IPS\\mpcservers\\Directory','esc');
        $row=Presentation::get($topic->tid);
        if ($comment->mapped('first') && Presentation::enabled($topic->forum_id) && !Presentation::isDirectory($topic) && $topic->canView() && $topic->canEdit() && (($row && !empty($row['is_server'])) || OrdinaryTopic::allowed($topic))) {
            return '<a href="'.$e($topic->url()->setQueryString('do','edit')).'" data-sps-edit-publication>Edytuj publikację i tryby</a>';
        }
        return '<a href="'.$e($comment->url('edit')).'" data-action="editComment">'.$e(\IPS\Member::loggedIn()->language()->addToStack('edit')).'</a>';
    }
    /** IPS5: HTML before / after the first post (custom templates on postWrapper; IPS4 wrapped postContainer) */
    protected static array $parts=array();
    public static function parts($topic,$comment)
    {
        $key=(int)$comment->pid;
        if (!isset(static::$parts[$key])) {
            try { $html=static::layout($topic,$comment,"\x00MPCS_NATIVE\x00"); }
            catch (\Throwable $e) { Servers::logError($e); $html="\x00MPCS_NATIVE\x00"; }
            $p=explode("\x00MPCS_NATIVE\x00",$html,2);
            static::$parts[$key]=count($p)===2?$p:array('','');
        }
        return static::$parts[$key];
    }
    public static function before($topic,$comment) { return static::parts($topic,$comment)[0]; }
    public static function after($topic,$comment) { return static::parts($topic,$comment)[1]; }
    public static function post($topic,$comment) { return $comment->content(); }
    public static function layout($topic,$comment,$native)
    {
        $r=Presentation::get($topic->tid);
        if (!$comment->mapped('first') || !$r || !Presentation::visible($topic,$r)) {
            if ($comment->mapped('first') && Presentation::enabled($topic->forum_id) && !Presentation::isDirectory($topic) && $topic->canView() && $topic->canEdit() && OrdinaryTopic::allowed($topic)) { return $native.'<div class="ipsBox i-padding_3 i-margin-bottom_3">'.static::editLink($topic,$comment).'</div>'; }
            return $native;
        }
        $e=array('IPS\\mpcservers\\Directory','esc');
        $html=$r['mode']==='html' && Presentation::linkModeEnabled(Presentation::forumConfig($topic->forum_id)) && Presentation::allowedUrl($r['url'],$topic->forum_id);
        $out='<div class="sps-publication-layout">';
        if ($html) {
            $out.='<article class="ipsBox ipsComment sps-html-article" aria-label="Prezentacja HTML serwera"><div class="sps-html-heading">'.Presentation::iconHtml($r).'<h2>'.$e($topic->title).'</h2><a href="'.$e($r['url']).'" target="_blank" rel="noopener noreferrer nofollow ugc">Otwórz pełną prezentację ↗</a></div><iframe title="Prezentacja serwera" src="'.$e(\IPS\mpcservers\Modules\Metin2\PresentationProxy::url($topic->tid)).'" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" loading="lazy"></iframe></article><details class="sps-native-post"><summary class="ipsBox">Treść tematu - rozwiń standardowy post</summary>'.$native.'</details>';
        } else { $out.=$native; }
        $mode=Servers::topicBadges($topic);
        $date=function($timestamp) use ($e) {
            if (!$timestamp) { return 'Jeszcze nie podbijano'; }
            $date=\IPS\DateTime::ts((int)$timestamp);
            $date->setTimezone(new \DateTimeZone(\IPS\Member::loggedIn()->timezone ?: 'Europe/Warsaw'));
            return '<time datetime="'.$e($date->format('c')).'">'.$e($date->format('d.m.Y H:i')).'</time>';
        };
        $out.='<ul class="sps-publication-widgets"><li class="ipsWidget ipsWidget_horizontal ipsBox sps-publication-widget" data-sps-topic-boost data-url="'.$e(Presentation::url(array('do'=>'inlineVote'))).'" data-id="'.(int)$topic->tid.'" data-csrf="'.$e(\IPS\Session::i()->csrfKey).'"><div class="ipsWidget_inner sps-publication-statistics">';
        $out.='<div class="sps-publication-stat sps-publication-owner"><span class="i-color_soft">Autor</span>'.Directory::authorHtml($topic).'</div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Data powstania</span><strong>'.$date($topic->start_date).'</strong></div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Tryb serwera</span><strong>'.($mode?:'Nie podano').'</strong></div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Podbicia w tym tygodniu</span><strong data-sps-vote-total>'.Rankings::weekly($topic->tid).'</strong></div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Podbicia w roku '.(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Warsaw')))->format('Y').'</span><strong data-sps-year-total>'.Rankings::yearly($topic->tid).'</strong></div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Ostatnie podbicie</span><strong data-sps-last-boost>'.$date($r['bumped']).'</strong></div>';
        $out.='<div class="sps-publication-stat"><span class="i-color_soft">Otwarcie</span><strong>'.$e(str_replace('Otwarcie: ','',Presentation::opening($r))).'</strong></div>';
        if ($r['protocol']!=='none') { $out.='<div class="sps-publication-stat"><span class="i-color_soft">Status</span><strong>'.$e(Presentation::status($r)).'</strong></div>'; }
        $out.='</div><div class="ipsWidget_inner sps-publication-team" aria-label="Administracja serwera">';
        if(Registry::enabled('community')) {
        $configuredRoles=Crew::roles(Presentation::forumConfig($topic->forum_id)['category']);
        $teamRoles=array('owner'=>$configuredRoles['owner'],'technician'=>$configuredRoles['technician']);
        foreach(Crew::roster($topic->tid) as $assignment){$role=$assignment['role'];$teamRoles[$role]=$configuredRoles[$role]??$assignment['role_label'];}
        foreach($teamRoles as $role=>$label){$out.='<div class="sps-publication-stat"><span>'.$e($label).'</span><strong>'.Crew::roleHtml($topic,$role).'</strong></div>';}
        $out.=CrewLock::notice($topic);
        }
        $awardBadges=Awards::badges($topic->tid);
        $out.='</div>'.($awardBadges?'<div class="i-padding_3 sps-awards">'.$awardBadges.'</div>':'').'<footer class="sps-presentation-footer">'.(Registry::enabled('highlights')?'<a href="'.$e(Rankings::url(array('category'=>$r['category']))).'">TOP serwery</a>':'').'<a href="'.$e(Presentation::url()).'">Zobacz listę serwerów</a>';
        if (Boost::rewardsEnabled($r['category'],$r['tid']) && \IPS\mpcservers\GameRewards::supports($r['category']) && $r['api_hash'] && OwnerAccess::isOwner($topic->tid,$r['api_owner']) && \IPS\Member::loggedIn()->member_id) {
            $out.='<details class="sps-presentation-reward"><summary>Odbierz kod nagrody przy podbiciu (opcjonalnie)</summary><label>Nick Minecraft<input data-sps-reward-player maxlength="16" pattern="[A-Za-z0-9_]{3,16}" autocomplete="off"></label></details>';
        }
        if (Recommended::canManage($topic)) { $out.='<a href="'.$e(Recommended::url($topic)).'">'.(!empty($r['featured'])?'Zmień polecenie MPC':'Poleć serwer').'</a>'; }
        if (Registry::enabled('community') && CrewLock::canModerate($topic)) { $out.='<a href="'.$e(Crew::url(array('do'=>'lock','tid'=>$topic->tid))).'">Blokada ekipy - moderacja</a>'; }
        if (Crew::canManage($topic)) { $out.='<a href="'.$e(Crew::url(array('do'=>'edit','tid'=>$topic->tid))).'">Edytuj ekipę serwera</a>'; }
        if ($topic->canEdit()) { $out.='<div class="sps-presentation-edit">'.static::editLink($topic,$comment).'</div>'; }
        $blocked=$topic->state==='closed'?'Temat jest zamknięty.':Boost::blockedReason($topic->tid,(int)\IPS\Member::loggedIn()->member_id);
        $disabled=$blocked!=='';
        $out.='<button type="button" data-sps-topic-vote class="ipsButton ipsButton--primary"'.($disabled?' disabled':'').'>↑ Podbij serwer</button><p class="i-color_soft">'.($disabled?$e($blocked):'Możesz podbić 5 różnych serwerów dziennie, każdy raz. Limit odnawia się następnego dnia. Licznik tygodniowy resetuje się w niedzielę o 23:59. Podbicie przesuwa serwer na liście. Temat odświeża się dopiero po 7 dniach bez aktywności.').'</p><div data-sps-topic-result role="status" aria-live="polite"></div><noscript><a href="'.$e(Presentation::url(array('do'=>'vote','id'=>$topic->tid))).'">Przejdź do podbijania</a></noscript></footer></li></ul></div>';
        return $out.static::assets();
    }
    public static function assets()
    {
        return <<<'HTML'
<style>
.sps-publication-layout{width:100%;min-width:0}.sps-html-article{overflow:hidden;margin:0 0 16px;border-radius:12px}.sps-html-heading{padding:18px 22px;min-height:76px}.sps-html-heading h2{margin:0 0 8px;font-size:22px;overflow-wrap:anywhere}.sps-html-heading img{width:48px;height:48px}.sps-html-article iframe{display:block;width:100%;height:80vh;min-height:480px;border:0;background:#fff}.sps-native-post{margin:16px 0}.sps-native-post>summary{cursor:pointer;padding:16px 20px;font-weight:600;border-radius:8px}.sps-native-post[open]>summary{margin-bottom:12px}.sps-native-post>article{margin:0!important}.sps-publication-widgets{margin:16px 0!important;padding:0!important;list-style:none}.sps-publication-widget{margin:0!important;overflow:hidden}.sps-publication-statistics{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:20px;padding:22px!important;align-items:center}.sps-publication-team{display:flex;flex-wrap:wrap;gap:20px;padding:18px 22px!important;border-top:1px solid rgba(128,128,128,.2)}.sps-publication-team>.sps-publication-stat{flex:1 1 145px}.sps-publication-stat{display:flex;flex-direction:column;gap:8px;min-width:0;overflow-wrap:anywhere}.sps-publication-stat>span{font-size:12px}.sps-publication-stat>strong{font-size:14px;line-height:1.5}.sps-publication-stat [data-sps-vote-total]{font-size:24px}.sps-publication-owner .sps-server-author{display:flex;align-items:center;gap:8px}.sps-publication-owner .ipsUserPhoto{display:block;flex:0 0 28px;width:28px!important;height:28px!important;margin:0;overflow:hidden;border-radius:50%}.sps-publication-owner .ipsUserPhoto img{display:block;width:100%!important;height:100%!important;object-fit:cover}.sps-presentation-footer{border-top:1px solid rgba(128,128,128,.2);padding:18px 22px;display:flex;flex-wrap:wrap;align-items:center;gap:14px}.sps-presentation-footer>p,.sps-presentation-footer>[role=status],.sps-presentation-reward{flex-basis:100%;margin:0}.sps-presentation-footer>p{font-size:12px}.sps-presentation-footer button{margin-left:auto;min-width:180px;padding:12px 20px;border:0;border-radius:6px;background:var(--i-primary);color:var(--i-primary-contrast);font-weight:600;cursor:pointer}.sps-presentation-footer button:disabled{opacity:.6;cursor:default}.sps-presentation-reward summary{cursor:pointer}.sps-presentation-reward label{display:flex;flex-direction:column;gap:6px;margin-top:10px;max-width:300px}.sps-presentation-footer pre{white-space:pre-wrap;overflow-wrap:anywhere}.sps-native-post:not([open])>article{display:none!important}@media(max-width:650px){.sps-publication-team{padding:16px!important;gap:18px}.sps-html-heading{padding:16px}.sps-html-article iframe{min-height:380px;height:70vh}.sps-publication-statistics{grid-template-columns:repeat(2,minmax(0,1fr));padding:16px!important;gap:18px}.sps-presentation-footer{padding:16px}.sps-presentation-footer button{width:100%;margin:0}.sps-native-post .{margin-left:0!important;margin-right:0!important}}
</style>
<script>(function(){function init(){document.querySelectorAll('[data-sps-topic-boost]').forEach(function(root){if(root.dataset.ready)return;root.dataset.ready='1';const button=root.querySelector('[data-sps-topic-vote]');button.addEventListener('click',async function(){if(button.disabled)return;const output=root.querySelector('[data-sps-topic-result]'),input=root.querySelector('[data-sps-reward-player]');if(input&&!input.reportValidity())return;const data=new FormData();data.set('id',root.dataset.id);data.set('csrfKey',root.dataset.csrf);if(input)data.set('player',input.value.trim());button.disabled=true;output.textContent='Podbijanie…';try{const response=await fetch(root.dataset.url,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});const result=await response.json();if(!response.ok||!result.ok)throw Error(result.error||'Nie udało się podbić serwera.');output.textContent=result.message;const total=root.querySelector('[data-sps-vote-total]');total.textContent=String(result.votes);const annual=root.querySelector('[data-sps-year-total]');if(annual)annual.textContent=String(result.yearVotes);const last=root.querySelector('[data-sps-last-boost]');if(last)last.textContent=new Date().toLocaleString('pl-PL');if(result.code){const code=document.createElement('pre');code.textContent='/nagroda '+result.code;output.append(code,document.createTextNode('Kod ważny 24 godziny. Zapisz go teraz.'));}button.textContent='Serwer podbity';}catch(error){output.textContent=error.message||'Błąd połączenia. Spróbuj ponownie.';button.disabled=false;}});});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();if(window.jQuery)jQuery(document).on('contentChange',init);})();</script>
HTML;
    }
}
