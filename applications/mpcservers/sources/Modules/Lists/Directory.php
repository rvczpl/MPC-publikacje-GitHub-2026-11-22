<?php
namespace IPS\mpcservers\Modules\Lists;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\ModeFilters;
use IPS\mpcservers\Modules\Highlights\Recommended;
use IPS\mpcservers\Modules\Community\Owner;
use IPS\mpcservers\Modules\Lists\Boost;
use IPS\mpcservers\Modules\Highlights\Rankings;
use IPS\mpcservers\Modules\Highlights\Awards;
use IPS\mpcservers\Modules\Highlights\Highlights;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Core\Servers;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class Directory
{
    public static function esc($v) { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    public static function menu($game='', $active='', $extra='')
    {
        $items=array(
            'servers'=>array(Presentation::url($game?array('category'=>$game):array()),'list','Lista serwerów'),
            'people'=>array(Crew::url($game?array('game'=>$game):array()),'users','Osoby i powiązania'),
            'top'=>array(\IPS\Http\Url::internal('app=mpcservers&module=highlights&controller=browse','front')->setQueryString('game',$game),'trophy','TOP serwery')
        );
        if(!\IPS\mpcservers\Modules\Core\Registry::enabled('highlights'))unset($items['top']);
        if(!\IPS\mpcservers\Modules\Core\Registry::enabled('community'))unset($items['people']);
        if (\IPS\mpcservers\Modules\Core\Registry::enabled('community') && \IPS\Member::loggedIn()->member_id) $items['owners']=array(Owner::url(),'server','Moje serwery');
        $out='<nav class="sps-list-menu" aria-label="Menu serwerów">'.$extra;
        foreach($items as $key=>$item) $out.='<a href="'.static::esc($item[0]).'"'.($key===$active?' aria-current="page"':'').'><i class="fa fa-'.$item[1].'" aria-hidden="true"></i><span>'.$item[2].'</span></a>';
        return $out.'</nav>';
    }
    public static function requestedForums()
    {
        $ids=array_filter(array_map('intval',explode(',',(string)\IPS\Request::i()->forums)));
        return $ids ? array_values(array_intersect($ids,Presentation::forumIds())) : Presentation::forumIds();
    }
    public static function gameLanding($categories,$assets=TRUE)
    {
        $e=array(static::class,'esc');$backgrounds=json_decode((string)\IPS\Settings::i()->sps_game_icons,TRUE)?:array();
        $legacy=json_decode((string)\IPS\Settings::i()->sps_game_images,TRUE)?:array();
        $out='<section class="sps-directory sps-game-landing mpc-list-surface ipsBox i-padding_3"><header class="sps-list-heading">'.static::menu('','servers').'</header><h2 class="ipsTitle ipsTitle--h3">Wybierz grę</h2><p class="i-color_soft">Przejdź do listy serwerów wybranej gry.</p><nav class="sps-filters sps-game-menu" aria-label="Wybierz listę serwerów gry">';
        foreach($categories as $game=>$label){
            $url=\IPS\mpcservers\Media::url($backgrounds[$game]??($legacy[$game]??''));
            if(!$url || !Presentation::allowedIcon($url))$url=ModeFilters::asset(ModeFilters::gameArt($game));
            $out.='<a class="sps-mode-option" href="'.$e(Presentation::url(array('category'=>$game))).'" style="--sps-tile-accent:#738bff"><span class="sps-mode-badge sps-mode-graphic"><span class="sps-tile-art" aria-hidden="true"><img src="'.$e($url).'" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"></span><span class="sps-tile-caption">'.$e($label).'</span></span></a>';
        }
        if(!$categories)$out.='<p>Brak dostępnych gier.</p>';
        return $out.'</nav></section>'.($assets?static::assets():'');
    }
    public static function render($forums=NULL,$exclude=0,$assets=TRUE)
    {
        if(!\IPS\mpcservers\Modules\Core\Registry::enabled('lists'))return '';
        $ids=$forums===NULL?Presentation::forumIds():array_values(array_intersect($forums,Presentation::forumIds()));
        $req=\IPS\Request::i(); $cats=ModeFilters::activeGames($ids); $e=array(static::class,'esc');
        $cat=isset($cats[(string)$req->category])?(string)$req->category:'';
        if (!$exclude && $cat==='' && ($forums===NULL || count($cats)>1)) { return static::gameLanding($cats,$assets); }
        if (count($cats)===1) { $cat=(string)key($cats); }
        if ($exclude) {
            $scope=\IPS\forums\Topic::load((int)$exclude);
            if (!Presentation::isDirectory($scope) || !Presentation::enabled($scope->forum_id) || !$scope->canView() || $scope->hidden()) { throw new \OutOfRangeException; }
            $ids=array((int)$scope->forum_id);
            $cat=Presentation::forumConfig($scope->forum_id)['category'];
        }
        $showStatus=static::statusAvailable($ids,$cat);
        $columns=$showStatus?7:6;
        $q=mb_substr(trim((string)$req->q),0,100);
        $status=$showStatus && in_array((string)$req->status,array('online','offline','unknown'),TRUE)?(string)$req->status:'';
        $sort=in_array((string)$req->sort,array('votes','name','new','opening'),TRUE)?(string)$req->sort:'votes';
        $from=Presentation::validDate((string)$req->date_from)?(string)$req->date_from:'';
        $to=Presentation::validDate((string)$req->date_to)?(string)$req->date_to:'';
        $where=array(array('p.is_server=1'),array('t.approved=1'),array('t.state<>?','link'),array(\IPS\Db::i()->in('t.forum_id',$ids ?: array(0))));
        if ($exclude) { $where[]=array('p.tid<>?',(int)$exclude); }
        if ($cat!=='') { $where[]=array('p.category=?',$cat); }
        if ($q!=='') { $where[]=array('t.title LIKE ?','%'.str_replace(array('\\','%','_'),array('\\\\','\\%','\\_'),$q).'%'); }
        if ($status==='online' || $status==='offline') { $where[]=array('p.status=? AND p.checked>=?',$status,time()-900); }
        elseif ($status==='unknown') { $where[]=array('(p.checked<? OR p.status NOT IN (?,?))',time()-900,'online','offline'); }
        if ($from!=='') { $where[]=array('p.open_date>=?',$from); }
        if ($to!=='') { $where[]=array('p.open_date<>? AND p.open_date<=?','',$to); }
        // Prefix filters belong to one game: they appear after a game is chosen (or when the list has one game).
        $games=array(); foreach ($ids as $id) { $games[Presentation::forumConfig($id)['category']]=TRUE; }
        $groupForums=array();
        if ($cat!=='' || ($exclude && count($games)===1)) { foreach ($ids as $id) { if ($cat==='' || Presentation::forumConfig($id)['category']===$cat) { $groupForums[]=$id; } } }
        $groups=array();
        foreach ($groupForums as $id) {
            $cfg=Servers::enabledForum($id);
            foreach (($cfg['groups']??array()) as $key=>$group) {
                if (!isset($groups[$key])) { $groups[$key]=$group; }
                else { $groups[$key]['options']+=$group['options']; }
            }
        }
        $prefix=array();
        foreach ((array)$req->prefix as $g=>$v) { if (is_scalar($v) && isset($groups[$g]['options'][(string)$v])) { $prefix[$g]=(string)$v; } }
        $orders=array('votes'=>'p.votes DESC,p.bumped DESC,p.tid DESC','name'=>'t.title ASC,p.tid DESC','new'=>'p.tid DESC','opening'=>"(p.open_date='') ASC,p.open_date ASC,p.tid DESC");
        $page=max(1,min(10000,(int)$req->page)); $size=20; $skip=($page-1)*$size; $visible=0; $rows=array(); $more=FALSE;
        $query=\IPS\Db::i()->select('p.*',array('mpcservers_presentations','p'),$where,$orders[$sort])->join(array('forums_topics','t'),'t.tid=p.tid','INNER');
        $weekly=Rankings::counts(Rankings::period());
        $all=array(); foreach ($query as $r) { if(!\IPS\mpcservers\Modules\Core\Registry::gameFeature($r['category'],'recommended'))$r['featured']=0; $r['votes']=$weekly[$r['tid']]['votes']??0;$r['week_bumped']=$weekly[$r['tid']]['bumped']??0;$all[]=$r; }
        // Legacy sort=bump requests fall back to weekly votes, including AJAX fragments.
        if($sort==='votes'){usort($all,function($a,$b){$featured=(int)$b['featured']<=>(int)$a['featured'];if($featured)return $featured;if((int)$a['votes']!==(int)$b['votes'])return (int)$b['votes']<=>(int)$a['votes'];return ($b['week_bumped']<=>$a['week_bumped'])?:((int)$b['tid']<=>(int)$a['tid']);});}

        $champions=array();$podium=array();$recommended=array();
        foreach(array_keys($cats) as $game){if($cat!=='' && $game!==$cat)continue;$champions+=Highlights::winners($game);}
        foreach (array_chunk($all,100) as $chunk) {
            // One query per 100 entries instead of one per entry.
            $topics=Presentation::loadTopics(array_map(function($r){return (int)$r['tid'];},$chunk));
            foreach ($chunk as $r) {
                $topic=$topics[(int)$r['tid']]??NULL;
                if (!$topic || !Presentation::visible($topic,$r)) { continue; }
                if ($prefix) {
                    $attrs=Servers::attrRow($topic->tid,$topic->forum_id); $match=TRUE;
                    foreach ($prefix as $g=>$v) { if (!in_array($v,$attrs['attrs'][$g]??array(),TRUE)) { $match=FALSE; break; } }
                    if (!$match) { continue; }
                }
                if(!empty($r['featured'])){$recommended[]=array($r,$topic);continue;}
                if(isset($champions[(int)$topic->tid]))$podium[(int)$topic->tid]=$champions[(int)$topic->tid];
                if ($visible++<$skip) { continue; }
                if (count($rows)===$size) { $more=TRUE; continue; }
                $rows[]=array($r,$topic);
            }
        }
        $helpId=$exclude?'sps-list-help-topic-'.(int)$exclude:uniqid('sps-list-help-');
        $help=$exclude?'':'<button type="button" class="sps-help-button" data-sps-help aria-label="Zasady podbijania serwerów" aria-haspopup="dialog" aria-controls="'.$helpId.'">?</button>';
        $out='<section class="sps-directory mpc-list-surface ipsBox i-padding_3" data-sps-directory data-public-url="'.$e(Presentation::url()).'" data-standalone="'.($exclude?0:1).'"><header class="sps-list-heading">'.static::menu($cat,'servers',$help).'</header><dialog id="'.$helpId.'" class="sps-help-dialog ipsBox" aria-labelledby="'.$helpId.'-title"><header><h3 id="'.$helpId.'-title">Zasady podbijania</h3><button type="button" data-sps-help-close aria-label="Zamknij informacje">×</button></header><p>Domyślnie kolejność według liczby podbić w bieżącym tygodniu, od największej. Przy remisie decyduje ostatnie podbicie. Reset w każdą niedzielę o 23:59. Historia pozostaje w statystykach i TOP-ach. Możesz podbić 5 różnych serwerów dziennie, każdy tylko raz. Limit odnawia się następnego dnia.</p></dialog><form class="sps-filters" data-sps-filters method="get" action="'.$e(Presentation::url()).'">';
        foreach (array('app'=>'mpcservers','module'=>'directory','controller'=>'browse','forums'=>implode(',',$ids),'exclude'=>(int)$exclude,'page'=>$page) as $k=>$v) { $out.='<input type="hidden" name="'.$k.'" value="'.$e($v).'">'; }
        $out.='<input type="hidden" name="category" value="'.$e($cat).'">';
        $out.=ModeFilters::render($groups,$prefix,$groupForums);
        $out.='<label>Szukaj<input type="search" name="q" maxlength="100" value="'.$e($q).'" placeholder="Nazwa serwera"></label>';
        $select=function($name,$label,$options,$value) use ($e) {
            $html='<label>'.$e($label).'<select name="'.$e($name).'">';
            foreach ($options as $k=>$text) { $html.='<option value="'.$e($k).'"'.((string)$value===(string)$k?' selected':'').'>'.$e($text).'</option>'; }
            return $html.'</select></label>';
        };
        if ($showStatus) $out.=$select('status','Status',array(''=>'Wszystkie','online'=>'Online / dostępny port','offline'=>'Brak odpowiedzi','unknown'=>'Nieznany'),$status);
        $out.=$select('sort','Sortowanie',array('votes'=>'Podbicia w tym tygodniu','name'=>'Nazwa A-Z','new'=>'Najnowsze wpisy','opening'=>'Data otwarcia'),$sort);
        $fullGameLink='<div class="sps-full-game-link" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin:0 0 14px"><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.$e(Presentation::url()).'">Wybierz grę</a></div>';
        if ($exclude) {
            $fullGameLink='<div class="sps-full-game-link" style="display:flex;justify-content:flex-end;margin:0 0 14px"><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.$e(Presentation::url()).'">Wybierz grę</a></div>';
        }

        if (!$exclude && $cat!=='' && isset($cats[$cat])) {
            $destination=Presentation::url(array('category'=>$cat));
            foreach ($groupForums as $forumId) {
                foreach ((array)Presentation::forumConfig($forumId)['directory_topics'] as $directoryId) {
                    try {
                        $directoryTopic=\IPS\forums\Topic::load((int)$directoryId);
                        if ((int)$directoryTopic->forum_id===(int)$forumId && $directoryTopic->canView() && !$directoryTopic->hidden() && Presentation::isDirectory($directoryTopic)) {
                            $destination=$directoryTopic->url();break 2;
                        }
                    } catch (\OutOfRangeException $exception) {}
                }
            }
            $fullGameLink='<div class="sps-full-game-link" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin:0 0 14px"><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.$e(Presentation::url()).'">Wybierz grę</a><a class="ipsButton ipsButton--small ipsButton--inherit" href="'.$e($destination).'" style="white-space:normal"><i class="fa-solid fa-list" aria-hidden="true"></i> Pełna lista serwerów '.$e($cats[$cat]).'</a></div>';
        }
        $out.='<label>Otwarcie od<input type="date" name="date_from" value="'.$e($from).'"></label><label>Otwarcie do<input type="date" name="date_to" value="'.$e($to).'"></label><noscript><button type="submit">Filtruj</button></noscript></form>'.$fullGameLink.'<div data-sps-result role="status" aria-live="polite"></div><div class="sps-server-rows sps-table-wrap mpc-list-table-wrap"><table class="ipsTable sps-server-table mpc-list-table'.($showStatus?'':' sps-without-status').'" role="table" aria-label="Lista serwerów"><thead><tr><th scope="col" class="sps-col-pos">#</th><th scope="col" class="sps-col-server">Serwer</th><th scope="col" class="sps-col-owner">Właściciel</th><th scope="col" class="sps-col-technician">Technik</th>'.($showStatus?'<th scope="col" class="sps-col-status">Status</th>':'').'<th scope="col" class="sps-col-opening">Otwarcie</th><th scope="col" class="sps-col-votes">Podbicia</th></tr></thead><tbody>';
        $allowance=Boost::dailyAllowance((int)\IPS\Member::loggedIn()->member_id);
        $position=$skip;
        $podiumHtml=$page===1 && $podium?'<tr class="sps-winner-podium"><td colspan="'.$columns.'">'.Highlights::cards($podium).'</td></tr>':'';
        foreach (array_merge($recommended,$rows) as list($r,$t)) {
            if(empty($r['featured'])){$out.=$podiumHtml;$podiumHtml='';$position++;}
            $out.='<tr class="sps-server-row'.(!empty($r['featured'])?' sps-featured':'').(isset($podium[(int)$t->tid])?' sps-current-winner':'').'" data-sps-row><td class="sps-col-pos" data-label="#">'.(!empty($r['featured'])?Recommended::badge():$position).'</td>';
            // Prefix badges at the start of the name, as in the forum's topic list and topic title.
            $topBadges='';
            if(empty($r['featured']))foreach($champions[(int)$t->tid]['awards']??array() as $award){
                $topBadges.='<span class="ipsBadge sps-top-award" style="display:inline-block;background:#6750a4;color:#fff;margin-right:4px;white-space:normal">'.$e($award['title']).'</span> ';
            }
            if(count($champions[(int)$t->tid]['awards']??array())>1 && empty($r['featured']))$topBadges='<span class="ipsBadge sps-top-award" style="background:#6750a4;color:#fff;margin-right:4px">TOP</span> ';
            $badges=Servers::topicBadges($t,FALSE);
            $highlightHtml=Recommended::titleTag($t).$topBadges;
            $versionLabel=\IPS\mpcservers\Modules\Minecraft\Versions::topicLabel($t);
            $versionHtml=$versionLabel!==''?' <span class="mpcs-b mpcs-version" style="display:inline-block;padding:0 7px;border-radius:3px;color:#fff;background:#047857;font-size:12px;font-weight:600">'.$e($versionLabel).'</span>':'';
            $out.='<td class="sps-server-body" data-label="Serwer"><a class="sps-server-main" href="'.$e($t->url()).'">'.Presentation::iconHtml($r).'<span class="sps-server-info"><span class="sps-server-name">'.$versionHtml.$highlightHtml.'<span class="sps-server-title" title="'.$e($t->title).'">'.$e($t->title).'</span> '.$badges.'</span>'.(!$exclude?'<span class="sps-server-meta">'.$e($cats[$r['category']]??$r['category']).'</span>':'').'<span class="sps-server-meta">'.$e(Presentation::connectionLabel($r,$t->forum_id)).'</span></span></a></td><td class="sps-server-owner" data-label="Właściciel">'.Crew::roleHtml($t,'owner').'</td><td data-label="Technik">'.Crew::roleHtml($t,'technician').'</td>';
            if ($showStatus) $out.='<td data-label="Status">'.$e(Presentation::status($r)).'</td>';
            $out.='<td data-label="Otwarcie">'.$e(str_replace('Otwarcie: ','',Presentation::opening($r))).'</td>';
            $out.='<td class="sps-server-vote" data-label="Podbicia"><span class="sps-vote-count">'.(int)$r['votes'].'</span>';
            $blocked=$t->state==='closed'?'Temat zamknięty':Boost::blockedReason($t->tid,(int)\IPS\Member::loggedIn()->member_id,$allowance);
            if ($blocked==='') {
                $reward=Boost::rewardsEnabled($r['category'],$r['tid']) && \IPS\mpcservers\GameRewards::supports($r['category']) && $r['api_hash'] && \IPS\mpcservers\OwnerAccess::isOwner($t->tid,$r['api_owner']);
                $out.='<details class="sps-vote-action"><summary class="sps-vote-plus" aria-label="Podbij serwer" title="Podbij serwer"><i class="fa-solid fa-plus" aria-hidden="true"></i></summary><form data-sps-vote method="post" action="'.$e(Presentation::url(array('do'=>'inlineVote'))).'"><input type="hidden" name="csrfKey" value="'.$e(\IPS\Session::i()->csrfKey).'"><input type="hidden" name="id" value="'.(int)$r['tid'].'">';
                if ($reward) { $out.='<label>Nick Minecraft - opcjonalnie dla nagrody<input name="player" maxlength="16" pattern="[A-Za-z0-9_]{3,16}" autocomplete="off"></label>'; }
                $out.='<button type="submit" class="ipsButton ipsButton--small ipsButton--primary">Potwierdź podbicie</button><span data-sps-vote-result role="status"></span></form></details>';
            } else { $out.='<span class="sps-vote-plus sps-vote-disabled" role="img" aria-label="'.$e($blocked).'" title="'.$e($blocked).'"><i class="fa-solid fa-plus" aria-hidden="true"></i></span>'; }
            $out.='</td></tr>';
        }
        $out.=$podiumHtml;
        if (!$rows && !$recommended) { $out.='<tr><td colspan="'.$columns.'" class="i-padding_3">Brak serwerów spełniających kryteria.</td></tr>'; }
        $state=array('category'=>$cat,'q'=>$q,'status'=>$status,'sort'=>$sort,'date_from'=>$from,'date_to'=>$to,'prefix'=>$prefix,'forums'=>implode(',',$ids),'exclude'=>(int)$exclude);
        $out.='</tbody></table></div><nav class="sps-pagination" aria-label="Strony spisu">';
        if ($page>1) { $out.='<a data-sps-page="'.($page-1).'" href="'.$e(Presentation::url($state+array('page'=>$page-1))).'">← Poprzednia</a>'; }
        $out.='<span>Strona '.$page.'</span>';
        if ($more) { $out.='<a data-sps-page="'.($page+1).'" href="'.$e(Presentation::url($state+array('page'=>$page+1))).'">Następna →</a>'; }
        return $out.'</nav></section>'.($podium?Highlights::css():'').($assets?static::assets():'');
    }
    /**
     * Author with the forum's native photo and link (profile card on hover).
     */
    public static function statusAvailable(array $forumIds, string $category): bool
    {
        if (!\IPS\Settings::i()->sps_status_enabled || !\IPS\mpcservers\Modules\Core\FeatureHooks::enabled('status')) { return FALSE; }
        foreach ($forumIds as $id) {
            $cfg=Presentation::forumConfig($id);
            if ($category!=='' && $cfg['category']!==$category) { continue; }
            $profile=Presentation::gameProfile($cfg);
            if (!empty($profile['connection']) && count(array_diff(array_keys($profile['protocols']),array('none')))) { return TRUE; }
        }
        return FALSE;
    }

    public static function authorHtml($topic)
    {
        try {
            $author=$topic->author();
            if (!$author || !$author->member_id) { return '<div class="sps-server-author">'.static::esc($topic->starter_name).'</div>'; }
            $global=\IPS\Theme::i()->getTemplate('global','core','front');
            return '<div class="sps-server-author">'.$global->userPhoto($author,'tiny').'<span>'.$global->userLink($author).'</span></div>';
        } catch (\Throwable $e) { Servers::logError($e); return ''; }
    }
    public static function assets()
    {
        return <<<'HTML'
<style>
/* Shared visual contract: ModLogs, server lists and MPC Creators. */
.mpc-list-surface {
  --mpc-list-border: color-mix(in srgb, var(--i-color_hard) 12%, transparent);
  --mpc-list-header: color-mix(in srgb, var(--i-color_hard) 4%, transparent);
  --mpc-list-hover: color-mix(in srgb, var(--i-color_hard) 3%, transparent);
  --mpc-list-radius: 10px;
}
.mpc-list-surface .mpc-list-table-wrap {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow-x: auto;
  border: 1px solid var(--mpc-list-border);
  border-radius: var(--mpc-list-radius);
  -webkit-overflow-scrolling: touch;
}
.mpc-list-surface .mpc-list-table {
  width: 100%;
  margin: 0;
  table-layout: fixed;
  border-collapse: collapse;
}
.mpc-list-surface .mpc-list-table thead th {
  padding: 12px;
  border-bottom: 1px solid var(--mpc-list-border);
  background: var(--mpc-list-header);
  color: var(--i-color_soft);
  font-size: 12px;
  font-weight: 600;
  line-height: 1.5;
  text-align: left;
  text-transform: uppercase;
  letter-spacing: .02em;
  white-space: normal;
  overflow-wrap: anywhere;
}
.mpc-list-surface .mpc-list-table tbody td {
  padding: 12px;
  border-bottom: 1px solid var(--mpc-list-border);
  vertical-align: middle;
  line-height: 1.5;
  white-space: normal;
  overflow-wrap: anywhere;
}
.mpc-list-surface .mpc-list-table tbody tr:last-child td {
  border-bottom: 0;
}
.mpc-list-surface .mpc-list-table tbody tr:hover td,
.mpc-list-surface .mpc-list-table tbody tr:focus-within td {
  background: var(--mpc-list-hover);
}
.mpc-list-surface .mpc-list-table a:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 3px;
  border-radius: 3px;
}

.sps-filter-section{margin:0 0 10px;border:1px solid rgba(128,128,160,.26);border-radius:10px;background:rgba(128,128,160,.04);min-width:0}.sps-filter-section>summary{display:flex;align-items:center;gap:12px;padding:13px 16px;cursor:pointer;list-style:none;min-height:54px}.sps-filter-section>summary::-webkit-details-marker{display:none}.sps-filter-section>summary:after{content:"+";font-size:22px;line-height:1;margin-left:4px;flex:none}.sps-filter-section[open]>summary:after{content:"-"}.sps-filter-section>summary:focus-visible{outline:2px solid #939dff;outline-offset:2px;border-radius:8px}.sps-filter-heading{font-weight:600;flex:1;min-width:0;overflow-wrap:anywhere}.sps-filter-heading small{font-size:12px;font-weight:400;opacity:.65;margin-left:10px;white-space:nowrap}.sps-filter-current{font-size:12px;border:1px solid rgba(128,128,160,.3);padding:5px 10px;border-radius:6px;max-width:50%;overflow-wrap:anywhere}.sps-filter-selected .sps-filter-current{color:#c5ccff;background:#738bff22;border-color:#738bff}.sps-filter-section .sps-filter-legend{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.sps-filter-section .sps-mode-group{margin:0;padding:0 16px 16px}.sps-filter-section .sps-mode-options{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:10px;margin:0;max-height:340px;overflow:auto;padding:4px;scrollbar-width:thin}.sps-filter-section .sps-mode-badge{height:76px;border-radius:8px;box-shadow:none;container-type:normal}.sps-filter-section .sps-tile-caption,.sps-filter-section .sps-tile-caption-long{font-size:12px;line-height:1.3;bottom:12px;left:8px;right:8px;overflow-wrap:anywhere}.sps-filter-section .sps-tile-check{width:19px;height:19px;line-height:19px;font-size:12px;top:5px;right:5px}.sps-filter-section .sps-mode-badge:after{background:linear-gradient(180deg,#080c1833,#080c18ed)}.sps-filter-section .sps-mode-option:hover .sps-mode-badge{transform:none}.sps-filter-section .sps-mode-option input:checked+.sps-mode-badge{box-shadow:0 0 0 1px var(--sps-tile-accent)}@media(max-width:600px){.sps-filter-section>summary{padding:12px;gap:8px}.sps-filter-heading small{display:block;margin:3px 0 0}.sps-filter-section .sps-mode-group{padding:0 8px 12px}.sps-filter-section .sps-mode-options{grid-template-columns:repeat(2,minmax(0,1fr));max-height:280px;gap:8px}.sps-filter-current{max-width:45%}}
.sps-game-menu.sps-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin-top:20px}.sps-game-menu .sps-mode-option{text-decoration:none}.sps-game-menu .sps-mode-badge{height:210px}.sps-game-menu .sps-mode-option:focus-visible{outline:3px solid #a5b1ff;outline-offset:4px;border-radius:14px}@media(max-width:600px){.sps-game-menu.sps-filters{grid-template-columns:1fr;gap:12px}.sps-game-menu .sps-mode-badge{height:165px}}
.sps-game-icon{position:absolute;top:14px;left:14px;display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:10px;background:rgba(10,15,30,.72);border:1px solid rgba(255,255,255,.25);color:white;font-size:27px}.sps-game-icon img{display:block;width:36px;height:36px;object-fit:contain}.sps-mode-filters,.sps-game-filters{flex:0 0 100%;width:100%;min-width:0}.sps-mode-group{border:0;padding:0;margin:0 0 18px;min-width:0;position:relative}.sps-mode-group legend{font-size:14px;font-weight:600;margin-bottom:10px}.sps-mode-options{display:flex;flex-wrap:wrap;gap:16px;width:100%;margin-top:12px}.sps-filters .sps-mode-option{display:block;flex:1 1 145px;min-width:0;position:relative;cursor:pointer;max-width:100%;margin:0}.sps-mode-option input,.sps-tile-reset input{position:absolute;width:1px!important;height:1px;margin:0;opacity:0}.sps-mode-badge{display:block;position:relative;container-type:inline-size;height:clamp(128px,15vw,205px);border:2px solid var(--sps-tile-accent,#665cff);border-radius:14px;overflow:hidden;background:#111524;isolation:isolate;box-shadow:0 3px 16px #0004;transition:transform .18s,box-shadow .18s}.sps-tile-art{display:flex;position:absolute;inset:0;z-index:-2}.sps-tile-art img{display:block;flex:1 1 0;width:0;min-width:0;height:100%;object-fit:cover;object-position:center}.sps-mode-badge:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 20%,rgba(7,10,25,.25) 45%,rgba(7,10,25,.94) 100%);z-index:-1}.sps-tile-caption{position:absolute;bottom:16px;left:10px;right:10px;text-align:center;text-transform:uppercase;font-weight:800;font-size:14px;line-height:1.15;color:white;text-shadow:0 2px 6px #000;overflow-wrap:normal;word-break:normal;hyphens:none}.sps-tile-caption-long{font-size:12px}.sps-tile-check{display:none;position:absolute;top:8px;right:8px;width:25px;height:25px;border-radius:50%;background:var(--sps-tile-accent);color:white;text-align:center;line-height:25px;font-weight:700}.sps-mode-option input:checked+.sps-mode-badge{box-shadow:0 0 0 2px var(--sps-tile-accent),0 0 22px color-mix(in srgb,var(--sps-tile-accent) 45%,transparent)}.sps-mode-option input:checked+.sps-mode-badge .sps-tile-check{display:block}.sps-mode-option input:focus-visible+.sps-mode-badge{outline:3px solid white;outline-offset:4px}.sps-mode-option:hover .sps-mode-badge{transform:translateY(-3px);box-shadow:0 0 16px var(--sps-tile-accent)}.sps-filters .sps-tile-reset{position:relative;display:inline-flex;flex:none;width:auto;cursor:pointer;margin:0}.sps-tile-reset>span{display:block;padding:5px 12px;border:1px solid rgba(128,128,128,.45);border-radius:20px;font-size:12px}.sps-tile-reset input:checked+span{background:rgba(110,125,255,.2);border-color:#828bff}.sps-tile-reset input:focus-visible+span{outline:2px solid currentColor;outline-offset:3px}@media(max-width:600px){.sps-mode-options{gap:10px}.sps-filters .sps-mode-option{flex-basis:calc(50% - 10px)}.sps-mode-badge{height:135px;border-radius:10px}.sps-tile-caption{font-size:14px;bottom:12px}.sps-tile-caption-long{font-size:12px}}@supports(font-size:1cqw){.sps-tile-caption{font-size:clamp(12px,9cqw,22px)}.sps-tile-caption-long{font-size:clamp(11px,7cqw,18px)}}@media(prefers-reduced-motion:reduce){.sps-mode-badge{transition:none}.sps-mode-option:hover .sps-mode-badge{transform:none}}

.sps-directory{margin:16px 0;min-width:0;container:mpc-server-list / inline-size}.sps-list-heading{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:18px}.sps-list-title{display:flex;align-items:center;flex-wrap:wrap;gap:10px}.sps-directory-topic>.sps-list-title h1{margin:0}.sps-list-menu>.sps-help-button{align-self:center;margin:0 8px}.sps-help-button{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:28px;height:28px;border:1px solid #858ddb;border-radius:50%;background:rgba(128,128,190,.1);color:inherit;font:inherit;font-weight:700;cursor:pointer}.sps-help-button:hover{background:rgba(128,128,190,.25)}.sps-help-button:focus-visible{outline:2px solid #8b91ef;outline-offset:3px}.sps-help-dialog{width:min(520px,calc(100% - 32px));max-height:80vh;overflow:auto;padding:24px;border:1px solid rgba(128,128,160,.4);border-radius:12px;background:var(--i-background_1);color:inherit;box-shadow:0 20px 80px #0006}.sps-help-dialog::backdrop{background:rgba(0,0,0,.65)}.sps-help-dialog header{display:flex;align-items:center;justify-content:space-between;gap:16px}.sps-help-dialog h3{margin:0;font-size:18px}.sps-help-dialog p{line-height:1.8;margin:18px 0 0}.sps-help-dialog button{background:transparent;border:0;color:inherit;font-size:26px;cursor:pointer;padding:0 5px}.sps-list-heading h2{margin:0;flex:1 1 240px;min-width:0;overflow-wrap:anywhere}.sps-list-menu{display:flex;flex-wrap:wrap;gap:4px;margin-left:auto;padding:4px;border:1px solid rgba(128,128,160,.25);border-radius:9px;background:rgba(128,128,160,.07)}.sps-list-menu a{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:9px 13px;border-radius:6px;color:inherit!important;text-decoration:none!important;font-size:13px;font-weight:600;line-height:1.4;min-height:38px}.sps-list-menu a[aria-current="page"]{background:color-mix(in srgb,var(--i-primary) 14%,transparent);color:var(--i-color_hard)!important}.sps-list-menu a:hover{background:rgba(128,128,190,.18)}.sps-list-menu a:focus-visible{outline:2px solid #8b91ef;outline-offset:1px;background:rgba(128,128,190,.18)}.sps-list-menu .fa{color:#929ae9}@media(max-width:700px){.sps-list-menu{width:100%;margin-left:0}.sps-list-menu a{flex:1 1 135px;padding:9px 8px}}.sps-filters{display:flex;flex-wrap:wrap;gap:12px;margin:20px 0}.sps-filters label{display:flex;flex:1 1 155px;flex-direction:column;gap:6px}.sps-filters>button{align-self:end;min-height:38px}.sps-filters input,.sps-filters select{width:100%;min-width:0;max-width:100%;box-sizing:border-box}
.sps-table-wrap{width:100%;max-width:100%;overflow-x:auto}.sps-server-table{width:100%;table-layout:fixed;border-collapse:collapse}.sps-directory .sps-server-table th{padding:10px 12px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.02em;color:var(--i-color_soft);border-bottom:1px solid rgba(128,128,128,.3);white-space:normal;overflow-wrap:anywhere}.sps-directory .sps-server-table td{padding:10px 12px;vertical-align:middle;border-bottom:1px solid rgba(128,128,128,.2);white-space:normal;overflow-wrap:anywhere}.sps-server-table tbody tr:hover td{background:color-mix(in srgb, var(--i-color_hard) 3%, transparent)}.sps-col-pos .sps-mpc-recommends{margin:0!important}.sps-col-pos{width:42px;text-align:center!important;font-weight:700;font-variant-numeric:tabular-nums}.sps-col-server{width:32%}.sps-col-owner{width:18%}.sps-col-votes{width:110px}.sps-server-main{display:flex;align-items:center;gap:10px;color:inherit!important;text-decoration:none!important;min-height:40px}.sps-server-info{display:block;min-width:0;flex:1;line-height:1.5}.sps-server-main:hover .sps-server-name{text-decoration:underline}.sps-server-main img{width:40px!important;height:40px!important;margin:0!important;float:none!important;flex:0 0 40px;align-self:center}.sps-server-name{font-weight:700;overflow-wrap:anywhere}.sps-server-meta{display:block;margin-top:4px;font-size:.9em;overflow-wrap:anywhere}.sps-server-meta:empty{display:none}.sps-server-author{display:flex;align-items:center;gap:8px;margin-top:0;min-width:0}.sps-directory .sps-server-author>.ipsUserPhoto{display:block!important;position:relative;flex:0 0 28px;width:28px!important;height:28px!important;min-width:28px;max-width:28px;margin:0!important;padding:0!important;line-height:0;overflow:hidden;border-radius:50%;box-sizing:border-box}.sps-directory .sps-server-author>.ipsUserPhoto img{display:block!important;width:100%!important;height:100%!important;max-width:100%;margin:0!important;object-fit:cover;border-radius:inherit}.sps-directory .sps-server-author>span{display:block;line-height:1.4}.sps-recommended{display:inline-block;margin-left:8px;color:#c68a19;font-weight:600;font-size:.9em}.sps-featured td{background:rgba(212,165,20,.12)!important}.sps-featured td:first-child{box-shadow:inset 3px 0 #d5ac48}.sps-vote-count{font-size:1.3em;font-weight:700;font-variant-numeric:tabular-nums}.sps-vote-action{display:inline}.sps-vote-plus{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;margin-left:8px;vertical-align:middle;border:1px solid rgba(46,158,91,.4);border-radius:7px;background:rgba(46,158,91,.12);color:#2eae64;cursor:pointer;list-style:none}.sps-vote-plus::-webkit-details-marker{display:none}.sps-vote-plus::marker{content:""}.sps-vote-plus:hover,.sps-vote-plus:focus-visible{background:rgba(46,158,91,.25);outline:2px solid #2eae64;outline-offset:2px}.sps-vote-disabled{opacity:.4;cursor:default}.sps-server-author .ipsUserPhoto{flex-shrink:0}.sps-server-owner .sps-server-author>span{min-width:0;overflow-wrap:anywhere}.sps-server-vote form{padding-top:10px;text-align:left}.sps-server-vote label{display:flex;flex-direction:column;gap:6px;margin-bottom:8px}.sps-server-vote input,.sps-server-vote button{width:100%;box-sizing:border-box;white-space:normal}.sps-directory .sps-server-table .sps-server-vote button.ipsButton{display:block;width:100%;max-width:100%;min-width:0;height:auto;min-height:36px;padding:8px 5px!important;line-height:1.4!important;font-size:12px!important;white-space:normal!important;text-overflow:clip!important;overflow:visible!important;overflow-wrap:anywhere;box-sizing:border-box}.sps-server-vote [role=status]{display:block;margin-top:8px}.sps-pagination{display:flex;justify-content:center;gap:22px;margin-top:20px}.sps-directory[aria-busy=true] .sps-server-rows{opacity:.5}.sps-directory [data-sps-result]:not(:empty){padding:14px;border:1px solid rgba(128,128,128,.4);margin:12px 0}.sps-directory pre{white-space:pre-wrap;overflow-wrap:anywhere}
@container mpc-server-list (max-width:700px){.sps-server-table,.sps-server-table tbody{display:block}.sps-server-table thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}.sps-server-table tbody{display:grid;gap:12px;padding:8px}.sps-server-table tr{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);min-width:0;border:1px solid var(--mpc-list-border,color-mix(in srgb,var(--i-color_hard) 12%,transparent));border-radius:8px;padding:4px}.sps-directory .sps-server-table tbody td{display:block;min-width:0;width:auto;border:0;padding:8px}.sps-server-table td:before{content:attr(data-label);display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.02em;color:var(--i-color_soft);margin-bottom:5px}.sps-server-table .sps-col-pos{grid-column:1/-1;text-align:left!important}.sps-server-table .sps-col-pos:before{display:inline;margin-right:5px}.sps-server-table .sps-server-body{grid-column:1/-1}.sps-server-table .sps-server-body:before{display:none}.sps-server-table .sps-server-vote{grid-column:1/-1}.sps-server-table td[colspan]{grid-column:1/-1}.sps-filters label{flex-basis:130px}}

/* 6.1.8.5: reserve most of the row for the title and publication prefixes. */
.sps-directory .sps-server-table .sps-col-pos{width:36px}
.sps-directory .sps-server-table .sps-col-votes{width:88px}
.sps-directory .sps-server-table .sps-col-server{width:calc(64% - 124px)}
.sps-directory .sps-server-table .sps-col-owner,.sps-directory .sps-server-table .sps-col-technician{width:8%}
.sps-directory .sps-server-table .sps-col-status,.sps-directory .sps-server-table .sps-col-opening{width:10%}
.sps-directory .sps-without-status .sps-col-owner,.sps-directory .sps-without-status .sps-col-technician{width:10%}
.sps-directory .sps-without-status .sps-col-server{width:calc(70% - 124px)}
.sps-server-table .sps-server-name{display:flex;align-items:center;gap:5px;min-width:0}
.sps-server-table .sps-server-title{flex:0 1 auto;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sps-server-table .sps-server-name>.mpcs-badges{display:inline-flex;align-items:center;flex:none;white-space:nowrap}
.sps-server-table .sps-server-name>:is(.mpcs-b,.sps-recommended-title,.sps-top-award){flex:none;margin:0!important}
@container mpc-server-list (max-width:1100px){.sps-server-table .sps-server-name{flex-wrap:wrap}.sps-server-table .sps-server-title{white-space:normal;overflow:visible}.sps-server-table .sps-server-name>.mpcs-badges{flex-wrap:wrap;max-width:100%}}
</style>
<script>(function(){
if(!window.mpcDirectoryInit){window.mpcDirectoryInit=function(root){if(root.dataset.ready)return;root.dataset.ready='1';let timer,request;
const help=root.querySelector('.sps-help-dialog'),helpButton=root.querySelector('[data-sps-help]')||(root.closest('.sps-directory-topic')&&root.closest('.sps-directory-topic').querySelector(':scope > header [data-sps-help]'));
if(help&&helpButton){helpButton.setAttribute('aria-controls',help.id);helpButton.onclick=()=>help.showModal();help.querySelector('[data-sps-help-close]').addEventListener('click',()=>help.close());help.addEventListener('click',e=>{if(e.target===help){const b=help.getBoundingClientRect();if(e.clientX<b.left||e.clientX>b.right||e.clientY<b.top||e.clientY>b.bottom)help.close();}});}
const filters=root.querySelector('[data-sps-filters]');
async function refresh(page,notice){if(request)request.abort();request=new AbortController();const params=new URLSearchParams(new FormData(filters));params.set('page',page||1);params.set('do','fragment');const url=new URL(filters.action);url.search=params.toString();const active=document.activeElement,focus=active&&filters.contains(active)?active.name:null,focusValue=active&&active.type==='radio'?active.value:null,selection=focus&&active.type==='search'?active.selectionStart:null;root.setAttribute('aria-busy','true');try{const response=await fetch(url,{credentials:'same-origin',signal:request.signal,headers:{'X-Requested-With':'XMLHttpRequest'}});if(!response.ok)throw Error();const result=await response.json();const doc=new DOMParser().parseFromString(result.html,'text/html'),next=doc.querySelector('[data-sps-directory]');if(!next)throw Error();const opened=new Set(Array.from(root.querySelectorAll('details[data-sps-filter-group][open]'),el=>el.dataset.spsFilterGroup));next.querySelectorAll('details[data-sps-filter-group]').forEach(el=>{el.open=opened.has(el.dataset.spsFilterGroup);});root.replaceWith(next);window.mpcDirectoryInit(next);if(notice){const area=next.querySelector('[data-sps-result]');area.textContent=notice.message;if(notice.code){const code=document.createElement('pre');code.textContent='/nagroda '+notice.code;area.append(code,document.createTextNode('Kod ważny 24 godziny. Zapisz go teraz.'));}}if(focus){const target=Array.from(next.querySelectorAll('[name]')).find(el=>el.name===focus&&(focusValue===null||el.value===focusValue));if(target){target.focus();if(selection!==null)target.setSelectionRange(selection,selection);}}if(root.dataset.standalone==='1'){params.delete('do');const publicUrl=new URL(root.dataset.publicUrl);publicUrl.search=params.toString();history.replaceState(null,'',publicUrl);}return true;}catch(err){if(err.name!=='AbortError'){root.querySelector('[data-sps-result]').textContent='Nie udało się odświeżyć listy. Spróbuj ponownie.';}return false;}finally{root.removeAttribute('aria-busy');}}
filters.addEventListener('submit',e=>{e.preventDefault();refresh(1);});filters.addEventListener('change',e=>{if(e.target.name==='category'){filters.querySelectorAll('input[name^="prefix["]').forEach(input=>{input.checked=input.value==='';});}clearTimeout(timer);refresh(1);});filters.addEventListener('input',e=>{if(e.target.type==='search'){clearTimeout(timer);timer=setTimeout(()=>refresh(1),300);}});
root.querySelectorAll('[data-sps-page]').forEach(a=>a.addEventListener('click',e=>{if(e.ctrlKey||e.metaKey||e.shiftKey)return;e.preventDefault();refresh(a.dataset.spsPage);}));
root.querySelectorAll('[data-sps-vote]').forEach(form=>form.addEventListener('submit',async e=>{e.preventDefault();const button=form.querySelector('button'),message=form.querySelector('[data-sps-vote-result]');if(button.disabled)return;button.disabled=true;message.textContent='Zapisywanie podbicia…';try{const response=await fetch(form.action,{method:'POST',body:new URLSearchParams(new FormData(form)),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}});const data=await response.json();if(!response.ok||!data.ok){message.textContent=data.error||'Nie udało się podbić serwera.';button.disabled=false;return;}message.textContent=data.message+(data.code?' /nagroda '+data.code+' - kod ważny 24 godziny.':'');await refresh(filters.elements.page.value,data);}catch(err){message.textContent='Brak potwierdzenia z forum. Sprawdź listę przed ponowną próbą.';button.disabled=false;}}));
};}document.querySelectorAll('[data-sps-directory]').forEach(window.mpcDirectoryInit);
})();</script>
HTML;
    }
}
