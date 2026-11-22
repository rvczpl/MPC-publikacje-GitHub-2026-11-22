(function(){
    'use strict';
    if(window.mpcSharedBadges)return;
    window.mpcSharedBadges=true;
    const selector='.mpcs-b,.sps-recommended,.sps-recommended-title,.sps-top-award';
    let pending=false;
    function update(){
        pending=false;
        document.querySelectorAll(selector).forEach(function(badge){
            if(badge.closest('[contenteditable="true"]'))return;
            const label=badge.textContent.trim();
            if(label && (!badge.hasAttribute('title') || badge.hasAttribute('data-mpc-badge-auto-title'))){
                badge.title=label;badge.setAttribute('data-mpc-badge-auto-title','');
            }
            Array.from(badge.childNodes).forEach(function(node){
                if(node.nodeType!==3 || !node.textContent.trim())return;
                const span=document.createElement('span');span.className='mpc-badge-label';
                node.replaceWith(span);span.append(node);
            });
        });
    }
    function init(){update();new MutationObserver(function(){if(!pending){pending=true;requestAnimationFrame(update);}}).observe(document.body,{childList:true,subtree:true,characterData:true});}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
