(function () {
    'use strict';
    if(window.mpcListPrefixes) return;
    window.mpcListPrefixes=true;
    const style=document.createElement('style');
    style.textContent='.mpcs-list-title{display:flex;flex-wrap:wrap;align-items:center;gap:4px 6px}.mpcs-list-title>[data-ips-hook="title"]{order:0;flex:0 1 auto}.mpcs-list-before{order:-1}.mpcs-list-after{order:1}.sps-top-award{background:#6750a4;color:#fff}.ipsData__title.mpcs-list-title>.mpcs-list-before,.ipsData__title.mpcs-list-title>.mpcs-list-after{display:inline-flex!important;flex-wrap:wrap;align-items:center;gap:4px}';
    document.head.appendChild(style);
    let pending=false;
    function update(){
        pending=false;
        document.querySelectorAll('[data-ips-hook="topicRow"] .ipsData__title').forEach(function(box){
            const title=box.querySelector(':scope > [data-ips-hook="title"]');
            if(!title)return;
            const badges=Array.from(box.querySelectorAll('.mpcs-b,.sps-recommended,.sps-top-award'));
            if(!badges.length)return;
            box.classList.add('mpcs-list-title');
            let before=box.querySelector(':scope > .mpcs-list-before'),after=box.querySelector(':scope > .mpcs-list-after');
            if(!before){before=document.createElement('span');before.className='ipsBadges mpcs-list-before';box.insertBefore(before,title);}
            if(!after){after=document.createElement('span');after.className='ipsBadges mpcs-list-after';title.after(after);}
            const beforeTitle=b=>b.matches('.mpcs-version,.sps-recommended,.sps-top-award');
            const front=badges.filter(beforeTitle);
            const rank=b=>b.classList.contains('mpcs-version')?0:(b.matches('.sps-recommended,.sps-top-award')?1:(b.dataset.mpcsRotateGroup==='server-modes'?2:3));
            front.sort((a,b)=>rank(a)-rank(b));
            const back=badges.filter(b=>!beforeTitle(b));
            back.sort((a,b)=>rank(a)-rank(b));
            [ [before,front],[after,back] ].forEach(function(pair){
                const [target,nodes]=pair;
                nodes.forEach(function(node,i){if(target.children[i]!==node)target.insertBefore(node,target.children[i]||null);});
            });
        });
    }
    function init(){update();new MutationObserver(function(){if(!pending){pending=true;requestAnimationFrame(update);}}).observe(document.body,{subtree:true,childList:true});}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
