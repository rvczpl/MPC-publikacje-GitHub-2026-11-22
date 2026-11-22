<?php
namespace IPS\mpcservers\Modules\Core;

use IPS\mpcservers\Modules\Core\Presentation;
use IPS\mpcservers\Modules\Core\OrdinaryTopic;
if (!\defined('\IPS\SUITE_UNIQUE_KEY')) { exit; }
class TopicForm
{
    public static function field($topic,$forum)
    {
        $cfg=Presentation::forumConfig($forum);$cfg['_forum']=(int)$forum;$cfg['_topic']=$topic?(int)$topic->tid:0;
        $profile=Presentation::gameProfile($cfg);
        $row=$topic ? Presentation::get($topic->tid) : NULL;
        $value=$row ?: array('is_server'=>1,'category'=>$cfg['category'],'mode'=>'post','url'=>'','host'=>'','port'=>$profile['port'],'protocol'=>'none','icon'=>'','open_date'=>'');
        return new \IPS\Helpers\Form\Custom('sps_details',$value,FALSE,array('getHtml'=>function($element) use ($cfg,$topic) {
            return (OrdinaryTopic::allowed($topic)?OrdinaryTopic::selector($topic):'').static::html((array)$element->value,$cfg);
        }),function($value) use ($forum,$topic) { if (!OrdinaryTopic::selected($topic)) { Presentation::normalize((array)$value,$forum,$topic ? $topic->tid : 0); } });
    }
    public static function html($v,$cfg)
    {
        $profile=Presentation::gameProfile($cfg);
        if (($v['category']??$cfg['category'])!==$cfg['category']) { $v['port']=$profile['port']; $v['protocol']='none'; }
        if (!isset($profile['protocols'][$v['protocol']??'none'])) { $v['protocol']='none'; }
        if (empty($v['host']) && ($v['protocol']??'none')==='none') { $v['port']=$profile['port']; }
        $v['category']=$cfg['category'];
        $e=function($x){return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
        $select=function($key,$label,$choices) use ($e,$v) {
            $out='<label class="sps-field">'.$e($label).'<select class="ipsInput ipsInput--select ipsInput--wide" name="sps_details['.$key.']" data-sps="'.$key.'">';
            foreach ($choices as $id=>$text) { $out.='<option value="'.$e($id).'"'.((string)($v[$key]??'')===(string)$id?' selected':'').'>'.$e($text).'</option>'; }
            return $out.'</select></label>';
        };
        $input=function($key,$label,$max,$type='text') use ($e,$v) {
            return '<label class="sps-field">'.$e($label).'<input class="ipsInput ipsInput--text ipsInput--wide" type="'.$type.'" name="sps_details['.$key.']" data-sps="'.$key.'" maxlength="'.$max.'" value="'.$e($v[$key]??'').'"></label>';
        };
        $out='<section class="sps-topic-form" data-sps-form data-unrestricted="'.(\IPS\Settings::i()->sps_restrict_domains?'0':'1').'" data-proxy="'.$e((string)\IPS\Http\Url::internal('app=mpcservers&module=directory&controller=proxy','front')->setQueryString(array('id'=>$cfg['_topic']??0,'forum'=>$cfg['_forum']??0,'csrfKey'=>\IPS\Session::i()->csrfKey))).'" data-hosts="'.$e(json_encode(array_values(array_filter(array_map('trim',preg_split('/\R/',$cfg['hosts'])))))).'"><input type="hidden" name="sps_details[__sent]" value="1">';
        $out.='<section class="sps-publication-options"><h2 class="ipsBox__header">Opcje publikacji</h2><div class="i-padding_3">';
        $modes=array('post'=>'Treść posta na forum'); if (Presentation::linkModeEnabled($cfg)) { $modes['html']='Prezentacja z linku'; }
        $out.=$select('mode','Sposób publikacji',$modes);
        if(Registry::kind($cfg['category'])==='minecraft')$out.=\IPS\mpcservers\Modules\Minecraft\Versions::html($v,$cfg['category']);
        $out.=$input('open_date','Data otwarcia serwera (opcjonalnie)',10,'date');
        if ($profile['connection']) {
            $out.=$input('host',$profile['host_label'],253).$input('port',$profile['port_label'],5,'text');
            $out.=$select('protocol','Sprawdzanie statusu',$profile['protocols']);
        }
        $out.='<ul data-sps-options-extra></ul></div></section>';
        $out.='<section class="sps-publication-crew"><h2 class="ipsBox__header">Ekipa serwera</h2><div class="i-padding_3"><ul data-sps-crew-extra></ul></div></section>';
        $out.='<section class="sps-publication-media"><h2 class="ipsBox__header">Prezentacja i ikona</h2><div class="i-padding_3">';
        if (Presentation::linkModeEnabled($cfg)) {
            $out.='<div data-sps-html>'.$input('url','Adres HTTPS prezentacji',2000,'url').'<p class="i-color_soft i-font-size_-1">Dozwolone domeny: '.$e(\IPS\Settings::i()->sps_restrict_domains?($cfg['hosts'] ?: 'brak - administrator musi dodać domenę w ustawieniach działu'):'dowolna publiczna domena HTTPS').'</p><button type="button" class="ipsButton ipsButton--small ipsButton--inherit" data-sps-preview>Podgląd prezentacji</button><p data-sps-message role="status"></p></div>';
        }
        $out.='<div class="sps-icon-panel"><img data-sps-icon-preview alt="Podgląd ikony serwera" referrerpolicy="no-referrer" hidden>'.$input('icon','Link HTTPS do ikony (opcjonalnie)',2000,'url').'<p class="i-color_soft i-font-size_-1">Ikona jest opcjonalna. PNG, JPG, GIF lub WebP. Zalecany kwadratowy obraz.</p></div></div></section></section>';
        return $out.static::assets();
    }
    public static function assets()
    {
        return <<<'HTML'
<style>
.sps-topic-form{width:100%;min-width:0}.sps-publication-sidebar{min-width:0}.ipsColumns__secondary.sps-publication-sidebar{flex-basis:360px;max-width:100%}.sps-publication-box{margin-bottom:var(--i-sp_3,16px)}.sps-topic-form>section{margin:0 0 18px;border-radius:var(--i-box--bo-ra,8px);overflow:hidden;background:var(--i-background_2)}.sps-topic-form>section>h2.ipsBox__header{margin:0}.sps-topic-form .i-padding_3{padding:16px}.sps-field{display:flex;flex-direction:column;gap:7px;margin:0 0 16px;font-weight:600}.sps-field input,.sps-field select{width:100%;max-width:100%;box-sizing:border-box;font-weight:400}.sps-field input:not([type=checkbox]):not([type=radio]),.sps-field select{min-height:38px}.sps-icon-panel img{width:64px;height:64px;object-fit:contain;margin-bottom:12px}.sps-topic-form [hidden],.sps-publication-sidebar [hidden]{display:none!important}.sps-topic-form .ipsFieldRow{padding:0;margin:0 0 12px;border:0;background:none}.sps-topic-form .ipsFieldRow__label{display:block;float:none;width:auto;text-align:left;padding:0;margin:0 0 8px;font-weight:600}.sps-topic-form .ipsFieldRow__content{margin:0!important}.sps-topic-form ul{list-style:none;margin:0;padding:0}.sps-topic-form .sps-crew-editor [data-crew-rows]{grid-template-columns:1fr}.sps-topic-form .sps-crew-row{grid-template-columns:1fr;gap:10px;padding:14px}.sps-topic-form .sps-crew-row [data-remove]{justify-self:start}.sps-topic-form .sps-crew-card-heading{grid-column:auto}.sps-topic-form .mpcs-pickrow{display:block}.sps-topic-form .mpcs-picks{display:flex;flex-wrap:wrap;gap:6px}.sps-preview-dialog{width:min(1100px,94vw);max-width:94vw;padding:0;border:0;border-radius:10px;background:var(--i-background_1);color:var(--i-color_hard)}.sps-preview-dialog::backdrop{background:#000b}.sps-preview-dialog header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px}.sps-preview-dialog iframe{display:block;width:100%;height:75vh;border:0}.sps-preview-dialog h2{margin:0;font-size:18px}.sps-publication-fallback{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:20px}@media(max-width:980px){.ipsColumns__secondary.sps-publication-sidebar{flex-basis:300px}.sps-publication-fallback{grid-template-columns:minmax(0,1fr) 300px}}@media(max-width:760px){.sps-publication-fallback{display:block}.sps-publication-sidebar .sps-topic-form{margin-top:18px}}
</style>
<script>(function(){
function setup(root){if(root.dataset.ready)return;root.dataset.ready='1';
const form=root.closest('form');if(!form)return;
/* IPS5: extension fields are appended at the end of the form (last tab). The options go to the right column of the main tab, as in IPS4 */
const originalRow=root.closest('.ipsFieldRow');
const title=form.querySelector('[name="topic_title"],[name="title"]');
const titleRow=title?title.closest('.ipsFieldRow'):null;
const mainPanel=(titleRow&&titleRow.closest('.ipsTabs__panel'))||form;
const columns=(titleRow&&titleRow.closest('.ipsColumns'))||mainPanel.querySelector('.ipsColumns');
let sidebar=columns?columns.querySelector(':scope>.ipsColumns__secondary'):null;
if(!sidebar&&columns){sidebar=document.createElement('div');sidebar.className='ipsColumns__secondary';columns.append(sidebar);}
if(!sidebar&&titleRow&&titleRow.parentElement){const list=titleRow.parentElement,wrap=document.createElement('div');wrap.className='sps-publication-fallback';list.before(wrap);wrap.append(list);sidebar=document.createElement('aside');wrap.append(sidebar);}
if(sidebar){sidebar.classList.add('sps-publication-sidebar');const box=document.createElement('div');box.className='sps-publication-box';box.append(root);sidebar.prepend(box);
if(originalRow){if(!originalRow.querySelector('[data-sps-kind]')){originalRow.hidden=true;}else{const label=originalRow.querySelector(':scope>.ipsFieldRow__label,:scope>label');if(label)label.hidden=true;if(titleRow&&!titleRow.contains(originalRow)&&originalRow.closest('.ipsTabs__panel')!==mainPanel){titleRow.after(originalRow);}}}}
const extra=root.querySelector('[data-sps-options-extra]');['[data-mpcs-picker]','.sps-crew-editor'].forEach(selector=>{const panel=form.querySelector(selector);if(panel){const row=panel.closest('.ipsFieldRow');const destination=selector==='.sps-crew-editor'?root.querySelector('[data-sps-crew-extra]'):extra;if(row){destination.append(row);if(selector==='.sps-crew-editor'){const label=row.querySelector(':scope>.ipsFieldRow__label,:scope>label');if(label)label.hidden=true;}}else{const li=document.createElement('li');li.className='ipsFieldRow';li.append(panel);destination.append(li);}}});
const field=k=>root.querySelector('[data-sps="'+k+'"]'),mode=field('mode'),url=field('url'),icon=field('icon'),preview=root.querySelector('[data-sps-icon-preview]');let dialog=null;
function safe(value){try{const u=new URL(value);return u.protocol==='https:'&&!u.username&&!u.password&&(!u.port||u.port==='443')?u:null;}catch(e){return null;}}
function close(){if(dialog){dialog.close();dialog.remove();dialog=null;}}
function update(){const html=root.querySelector('[data-sps-html]');if(html)html.hidden=mode.value!=='html';close();}
mode.addEventListener('change',update);update();
function updateIcon(){const u=safe(icon.value);if(u&&/\.(png|jpe?g|gif|webp)$/i.test(u.pathname)){preview.src=u.href;preview.hidden=false;}else{preview.removeAttribute('src');preview.hidden=true;}}
icon.addEventListener('input',updateIcon);preview.addEventListener('error',()=>{preview.hidden=true;});updateIcon();
const button=root.querySelector('[data-sps-preview]');if(button)button.addEventListener('click',()=>{const u=safe(url.value),hosts=JSON.parse(root.dataset.hosts).map(x=>x.toLowerCase()),message=root.querySelector('[data-sps-message]');if(!u||(root.dataset.unrestricted!=='1'&&!hosts.includes(u.hostname.toLowerCase()))){message.textContent='Podaj adres HTTPS w domenie dozwolonej dla tego działu.';return;}close();message.textContent='';dialog=document.createElement('dialog');dialog.className='sps-preview-dialog';dialog.setAttribute('aria-label','Podgląd prezentacji');const header=document.createElement('header'),title=document.createElement('h2'),dismiss=document.createElement('button');title.textContent='Podgląd prezentacji';dismiss.type='button';dismiss.className='ipsButton ipsButton--small ipsButton--inherit';dismiss.textContent='Zamknij';dismiss.addEventListener('click',close);header.append(title,dismiss);const iframe=document.createElement('iframe');iframe.title='Podgląd prezentacji';iframe.setAttribute('sandbox','allow-scripts allow-popups allow-popups-to-escape-sandbox');iframe.referrerPolicy='no-referrer';const proxy=new URL(root.dataset.proxy);proxy.searchParams.set('source',u.href);iframe.src=proxy.href;dialog.append(header,iframe);const note=document.createElement('p');note.className='i-padding_3 i-font-size_-1';note.textContent='Podgląd przez proxy. Część funkcji wymagających logowania lub zewnętrznych API może działać tylko w oryginalnej prezentacji.';dialog.append(note);document.body.append(dialog);dialog.addEventListener('cancel',e=>{e.preventDefault();close();});dialog.addEventListener('click',e=>{if(e.target===dialog)close();});dialog.showModal();});
}
function init(){document.querySelectorAll('[data-sps-form]').forEach(setup);}
function later(){setTimeout(init,0);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',later);else later();if(window.jQuery)jQuery(document).on('contentChange',init);
})();</script>
HTML;
    }
}
