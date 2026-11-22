<?php
namespace IPS\mpcservers\Modules\Community;

use IPS\mpcservers\Modules\Lists\Directory;
use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Lists\ModeFilters;
use IPS\mpcservers\Modules\Community\Crew;
use IPS\mpcservers\Modules\Community\CrewRating;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class CrewDirectory
{
 public static function render(){
  $e=array('IPS\\mpcservers\\Directory','esc');$q=\IPS\Request::i();$game=is_scalar($q->game)?(string)$q->game:'';$role=is_scalar($q->role)?(string)$q->role:'';$search=is_scalar($q->q)?trim((string)$q->q):'';$id=(int)$q->id;
  $cats=ModeFilters::activeGames(Presentation::forumIds());if(!isset($cats[$game]))$game='';$roles=array();
  foreach($game?array($game=>$cats[$game]):$cats as $key=>$label){$roles+=Crew::roles($key);}
  if(!isset($roles[$role]))$role='';$records=Crew::records($id?:NULL,$game,$role,(bool)$q->current);
  if($search!=='')$records=array_values(array_filter($records,function($r)use($search){return mb_stripos($r['name'],$search)!==FALSE;}));
  $title='Właściciele i ekipy serwerów'.($game?' - '.$cats[$game]:' - wszystkie gry');
  $out='<section class="sps-directory ipsBox i-padding_3 sps-people"><header class="sps-list-heading"><h2>'.$e($title).'</h2>'.Directory::menu($game,'people').'</header>';
  $out.='<form method="get" action="'.$e(\IPS\Settings::i()->base_url.'index.php').'" class="sps-filters" data-people-filters><input type="hidden" name="app" value="mpcservers"><input type="hidden" name="module" value="directory"><input type="hidden" name="controller" value="people">'.($id?'<input type="hidden" name="id" value="'.$id.'">':'');
  $out.=ModeFilters::games($cats,$game,'game');
  $out.='<label>Szukaj osoby<input type="search" name="q" maxlength="120" value="'.$e($search).'" placeholder="Nazwa osoby"></label><label>Rola<select name="role"><option value="">Wszystkie role</option>';
  foreach($roles as $k=>$name)$out.='<option value="'.$e($k).'"'.($role===$k?' selected':'').'>'.$e($name).'</option>';
  $out.='</select></label><label>Sortowanie<select name="order"><option value="default">Domyślne</option><option value="rating"'.($q->order==='rating'?' selected':'').'>Najwyżej oceniani</option></select></label><label>Powiązania<select name="current"><option value="0">Obecne i historyczne</option><option value="1"'.($q->current?' selected':'').'>Tylko obecne</option></select></label><button class="ipsButton ipsButton--small ipsButton--primary" type="submit">Filtruj</button></form>';
  $people=array();foreach($records as $r){$pid=$r['person_id'];if(!isset($people[$pid]))$people[$pid]=array('row'=>$r,'roles'=>array(),'games'=>array(),'active'=>array(),'all'=>array());$people[$pid]['roles'][$r['role_label']]=TRUE;$people[$pid]['games'][$r['game']]=$cats[$r['game']]??$r['game'];$people[$pid]['all'][$r['tid']]=$r;if(!$r['ended_at'])$people[$pid]['active'][$r['tid']]=$r;}
  if($q->order==='rating'){$ratings=array();foreach(array_keys($people) as $pid)$ratings[$pid]=CrewRating::summary($pid);uksort($people,function($a,$b)use($ratings){return ($ratings[$b]['average']<=>$ratings[$a]['average'])?:($ratings[$b]['count']<=>$ratings[$a]['count'])?:($a<=>$b);});}
  $out.=static::graph($records,$game);
  $page=max(1,(int)$q->page);$pages=max(1,(int)ceil(count($people)/30));$page=min($page,$pages);
  $out.='<div class="sps-server-rows sps-table-wrap"><table class="ipsTable sps-server-table sps-people-table"><thead><tr><th class="sps-col-pos" scope="col">#</th><th class="sps-col-person" scope="col">Osoba</th><th scope="col">Role</th>'.(!$game?'<th scope="col">Gry</th>':'').'<th scope="col">Obecne / wszystkie serwery</th><th scope="col">Serwery</th><th scope="col">Ocena osoby</th></tr></thead><tbody>';
  $position=($page-1)*30;
  foreach(array_slice($people,($page-1)*30,30,TRUE) as $pid=>$p){$r=$p['row'];$account='';$photo='';if($r['member_id']){$m=\IPS\Member::load($r['member_id']);if($m->member_id){$account='<a class="sps-person-profile" href="'.$e($m->url()).'" data-ipsTooltip title="Profil forum: '.$e($m->name).'" aria-label="Profil forum: '.$e($m->name).'"><i class="fa-solid fa-link" aria-hidden="true"></i></a>';$photo=\IPS\Theme::i()->getTemplate('global','core')->userPhoto($m,'tiny');}else{$account='';}}
   $links=array();foreach($p['all'] as $tid=>$server)$links[]='<a href="'.$e($server['topic']->url()).'">'.$e($server['topic']->title).'</a>';
   $out.='<tr><td class="sps-col-pos" data-label="#">'.(++$position).'</td><td class="sps-server-body" data-label="Osoba"><div class="sps-server-author">'.$photo.'<span><a class="sps-server-name" href="'.$e(Crew::url(array('id'=>$pid,'game'=>$game))).'">'.$e($r['name']).'</a> '.$account.'</span></div></td><td data-label="Role">'.$e(implode(', ',array_keys($p['roles']))).'</td>'.(!$game?'<td data-label="Gry">'.$e(implode(', ',$p['games'])).'</td>':'').'<td data-label="Obecne / wszystkie serwery">'.count($p['active']).' / '.count($p['all']).'</td><td data-label="Serwery">'.implode('<br>',$links).'</td><td data-label="Ocena osoby">'.CrewRating::html($pid).(CrewRating::allowed(Crew::person($pid))?'<br>'.CrewRating::link($pid):'').'</td></tr>';
  }
  if(!$people)$out.='<tr><td colspan="'.($game?6:7).'" class="i-padding_3">Brak osób spełniających kryteria. Ekipę dodasz w formularzu publikacji serwera.</td></tr>';
  $out.='</tbody></table></div><nav class="sps-pagination" aria-label="Strony rejestru">';
  $params=array('order'=>$q->order==='rating'?'rating':'default','id'=>$id,'game'=>$game,'role'=>$role,'q'=>$search,'current'=>(int)(bool)$q->current);
  if($page>1)$out.='<a href="'.$e(Crew::url($params+array('page'=>$page-1))).'">← Poprzednia</a>';
  $out.='<span>Strona '.$page.' z '.$pages.'</span>';
  if($page<$pages)$out.='<a href="'.$e(Crew::url($params+array('page'=>$page+1))).'">Następna →</a>';
  $out.='</nav><p class="i-color_soft i-font-size_-1">Powiązania deklarowane przez autorów publikacji i uprawnionych moderatorów. Liczby i historia dotyczą wybranej gry oraz filtrów.</p>';
  if($id && $records){$out.='<h2>Historia powiązań - '.$e($records[0]['name']).'</h2><div class="sps-table-wrap"><table class="ipsTable sps-server-table"><thead><tr><th>Serwer / gra</th><th>Rola</th><th>Od</th><th>Do</th><th>Zapis i autor zmiany</th></tr></thead><tbody>';foreach($records as $r){$out.='<tr><td class="sps-server-body" data-label="Serwer / gra"><a href="'.$e($r['topic']->url()).'">'.$e($r['topic']->title).'</a><br>'.$e($cats[$r['game']]??$r['game']).'</td><td data-label="Rola">'.$e($r['role_label']).'</td><td data-label="Od">'.$e($r['since_date']?:'Nie podano').'</td><td data-label="Do">'.($r['ended_at']?date('d.m.Y H:i',$r['ended_at']):'Obecnie').'</td><td data-label="Zapis i autor zmiany">'.date('d.m.Y H:i',$r['created_at']).' · '.static::actor($r['created_by']).($r['ended_at']?'<br>Zakończył: '.static::actor($r['ended_by']):'').'</td></tr>';}$out.='</tbody></table></div>';}
  return $out.'</section>'.Directory::assets().static::assets();
 }
 private static function actor($id){$m=\IPS\Member::load($id);return $m->member_id?'<a href="'.Directory::esc($m->url()).'">'.Directory::esc($m->name).'</a>':'Usunięte konto';}
 public static function graph($records,$game=''){
  $e=array('IPS\\mpcservers\\Directory','esc');$nodes=array();$edges=array();$limited=FALSE;
  foreach($records as $r){$a='p'.$r['person_id'];$b='s'.$r['tid'];if((!isset($nodes[$a])||!isset($nodes[$b]))&&count($nodes)>=100){$limited=TRUE;continue;}$nodes[$a]=array('label'=>$r['name'],'url'=>Crew::url(array('id'=>$r['person_id'],'game'=>$game)),'person'=>TRUE);$nodes[$b]=array('label'=>$r['topic']->title,'url'=>$r['topic']->url(),'person'=>FALSE);$edges[]=array($a,$b,$r['role_label'],!$r['ended_at']);}
  if(!$nodes)return '';$i=0;$count=count($nodes);foreach($nodes as &$node){$angle=2*M_PI*$i++/$count;$node['x']=500+390*cos($angle);$node['y']=260+190*sin($angle);}unset($node);
  $out='<details class="sps-graph-panel"><summary>Graf powiązań · '.count($nodes).' węzłów</summary><p>Pomarańczowy: osoba · niebieski: serwer · linia przerywana: zakończona współpraca. Wskaż węzeł, aby wyróżnić powiązania; kliknij, aby przejść do historii lub serwera.</p>'.($limited?'<p>Graf pokazuje do 100 węzłów. Zawęź filtr, aby zobaczyć konkretną ekipę. Tabela uwzględnia wszystkie wyniki.</p>':'').'<svg viewBox="0 0 1000 520" role="img" aria-label="Graf powiązań osób z serwerami">';
  foreach($edges as $edge){list($a,$b,$label,$active)=$edge;$out.='<line data-a="'.$a.'" data-b="'.$b.'" x1="'.$nodes[$a]['x'].'" y1="'.$nodes[$a]['y'].'" x2="'.$nodes[$b]['x'].'" y2="'.$nodes[$b]['y'].'" stroke="'.($active?'#6c92b6':'#6b6576').'" stroke-opacity=".45" '.(!$active?'stroke-dasharray="4 5"':'').'><title>'.$e($label).'</title></line>';}
  foreach($nodes as $key=>$n)$out.='<a href="'.$e($n['url']).'" data-node="'.$key.'" aria-label="'.$e($n['label']).'"><title>'.$e($n['label']).'</title><circle cx="'.$n['x'].'" cy="'.$n['y'].'" r="7" fill="'.($n['person']?'#e9a843':'#50b9ee').'"/><text x="'.$n['x'].'" y="'.($n['y']-12).'" text-anchor="middle" fill="currentColor" font-size="11">'.$e(mb_strimwidth($n['label'],0,24,'…')).'</text></a>';
  return $out.'</svg></details>';
 }
 public static function assets(){return <<<'HTML'
<style>.sps-person-profile{display:inline-flex;align-items:center;justify-content:center;margin-left:5px;padding:4px;color:inherit;opacity:.7;vertical-align:middle}.sps-person-profile:hover,.sps-person-profile:focus{opacity:1}.sps-people-table .sps-col-person{width:25%}.sps-people .sps-filters>button{align-self:end}.sps-people .sps-server-meta{font-size:12px}.sps-graph-panel{border:1px solid #7775;border-radius:10px;padding:16px;margin:20px 0}.sps-graph-panel summary{cursor:pointer;font-weight:bold}.sps-graph-panel svg{display:block;width:100%;max-height:580px}.sps-graph-panel line{transition:opacity .15s}.sps-graph-panel svg[data-focus] line{opacity:.08}.sps-graph-panel svg[data-focus] line.sps-connected{opacity:1;stroke-width:3}.sps-graph-panel a:focus circle{stroke:white;stroke-width:3}</style>
<script>(function(){document.querySelectorAll('[data-people-filters]').forEach(form=>{form.addEventListener('change',event=>{if(event.target.name==='game'){form.querySelector('[name=role]').value='';form.requestSubmit();}else if(['role','current','order'].includes(event.target.name)){form.requestSubmit();}});});document.querySelectorAll('.sps-graph-panel svg').forEach(svg=>{function clear(){svg.removeAttribute('data-focus');svg.querySelectorAll('.sps-connected').forEach(e=>e.classList.remove('sps-connected'));}function focus(e){const node=e.target.closest('[data-node]');if(!node)return;clear();svg.dataset.focus=node.dataset.node;svg.querySelectorAll('line').forEach(line=>{if(line.dataset.a===node.dataset.node||line.dataset.b===node.dataset.node)line.classList.add('sps-connected');});}svg.addEventListener('mouseover',focus);svg.addEventListener('focusin',focus);svg.addEventListener('mouseleave',clear);svg.addEventListener('focusout',clear);});})();</script>
HTML;
 }
}
