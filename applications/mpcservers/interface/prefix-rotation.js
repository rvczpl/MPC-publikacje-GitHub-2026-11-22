(function () {
    'use strict';
    if (window.mpcPrefixRotation) return;
    window.mpcPrefixRotation = true;
    const motion = matchMedia('(prefers-reduced-motion: reduce)');
    const states = new WeakMap();
    let groups = [], scheduled = false;
    const style = document.createElement('style');
    style.textContent = '[data-mpcs-rotation-hidden]{display:none!important}.mpcs-rotation-active{animation:mpcs-prefix-in .22s ease-out}@keyframes mpcs-prefix-in{from{opacity:.35}to{opacity:1}}';
    document.head.appendChild(style);
    function paint(state) {
        const rotating = state.nodes.length > state.slots && !motion.matches;
        const visible = new Set();
        for(let n=0;n<state.slots;n++)visible.add((state.index+n)%state.nodes.length);
        state.nodes.forEach(function(badge,i){
            badge.toggleAttribute('data-mpcs-rotation-hidden',rotating && !visible.has(i));
            badge.classList.toggle('mpcs-rotation-active',rotating && visible.has(i));
            if(!badge.hasAttribute('data-mpcs-original-title'))badge.dataset.mpcsOriginalTitle=badge.getAttribute('title')||'';
            badge.setAttribute('title',rotating?state.summary:badge.dataset.mpcsOriginalTitle);
        });
    }
    function scan() {
        scheduled=false;
        const found=new Map();
        document.querySelectorAll('[data-mpcs-rotate-group]').forEach(function(badge){
            const key=badge.dataset.mpcsRotateGroup;if(!key)return;
            const parent=badge.parentElement;
            if(!found.has(parent))found.set(parent,new Map());
            if(!found.get(parent).has(key))found.get(parent).set(key,[]);
            found.get(parent).get(key).push(badge);
        });
        groups=[];
        found.forEach(function(sets,parent){
            let old=states.get(parent);if(!old){old=new Map();states.set(parent,old);}
            sets.forEach(function(nodes,key){
                let state=old.get(key);
                if(!state || state.nodes.length!==nodes.length || nodes.some((node,i)=>state.nodes[i]!==node))state={parent:parent,nodes:nodes,index:0,slots:key==='server-modes'?1:3,summary:nodes.map(n=>n.textContent.trim()).join(', ')};
                old.set(key,state);groups.push(state);paint(state);
            });
            for(const key of old.keys())if(!sets.has(key))old.delete(key);
        });
    }
    function init(){
        scan();
        new MutationObserver(function(){if(!scheduled){scheduled=true;requestAnimationFrame(scan);}}).observe(document.body,{childList:true,subtree:true});
        motion.addEventListener('change',scan);
        setInterval(function(){
            if(document.hidden || motion.matches)return;
            groups.forEach(function(state){
                if(!state.parent.isConnected || state.nodes.length<=state.slots || state.parent.matches(':hover, :focus-within'))return;
                state.index=(state.index+state.slots)%state.nodes.length;paint(state);
            });
        },2500);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
