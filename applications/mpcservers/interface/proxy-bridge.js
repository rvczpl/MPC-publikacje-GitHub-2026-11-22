'use strict';
// In-memory compatibility only. No cookies or persistent storage of the forum are exposed.
['localStorage','sessionStorage'].forEach(function(name){try{void window[name].length;return;}catch(e){}var data=Object.create(null),store={getItem:function(k){return Object.prototype.hasOwnProperty.call(data,k)?data[k]:null;},setItem:function(k,v){data[k]=String(v);},removeItem:function(k){delete data[k];},clear:function(){data=Object.create(null);},key:function(i){return Object.keys(data)[i]||null;}};Object.defineProperty(store,'length',{get:function(){return Object.keys(data).length;}});try{Object.defineProperty(window,name,{value:store});}catch(e){}});
try{void document.cookie;}catch(e){var cookies=Object.create(null);try{Object.defineProperty(document,'cookie',{get:function(){return Object.keys(cookies).map(function(k){return k+'='+cookies[k];}).join('; ');},set:function(v){var pair=String(v).split(';')[0],pos=pair.indexOf('=');if(pos>0)cookies[pair.slice(0,pos)]=pair.slice(pos+1);}});}catch(e){}}
var original=new URL(config.source),endpoint=new URL(config.endpoint);
// Single-page presentations update hashes through History. Keep their source URL
// out of IPS navigation; an opaque iframe may reject even same-document changes.
['pushState','replaceState'].forEach(function(method){var native=history[method];history[method]=function(state,title,value){
    if(value===undefined||value===null)return native.call(this,state,title);
    var u=new URL(String(value),original.href),current=new URL(location.href);
    var local=(u.origin===original.origin&&u.pathname===original.pathname&&u.search===original.search)||(u.origin===current.origin&&u.pathname===current.pathname&&u.search===current.search);
    if(!local)return native.apply(this,arguments);
    current.hash=u.hash;
    try{return native.call(this,state,title,current.href);}catch(e){if(e.name!=='SecurityError')throw e;}
};});
// Module requests inherit credential mode. Authenticate only our signed gateway,
// including HTTP Basic on staging, never an arbitrary remote script endpoint.
var scriptSrc=Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype,'src');
if(scriptSrc&&scriptSrc.set&&scriptSrc.get)Object.defineProperty(HTMLScriptElement.prototype,'src',{configurable:scriptSrc.configurable,enumerable:scriptSrc.enumerable,get:scriptSrc.get,set:function(value){
    try{var u=new URL(String(value),original.href);if(this.type==='module'&&u.origin===endpoint.origin&&u.pathname===endpoint.pathname&&u.searchParams.get('controller')==='proxyajax'&&u.searchParams.get('cap')===config.token&&u.searchParams.get('asset')==='1')this.crossOrigin='use-credentials';}catch(e){}
    return scriptSrc.set.call(this,value);
}});
// Only CORS-requesting images (e.g. WebGL textures) use the bounded image proxy.
// Ordinary presentation images continue loading directly from the source.
var imageSrc=Object.getOwnPropertyDescriptor(HTMLImageElement.prototype,'src');
if(imageSrc&&imageSrc.set&&imageSrc.get)Object.defineProperty(HTMLImageElement.prototype,'src',{configurable:imageSrc.configurable,enumerable:imageSrc.enumerable,get:imageSrc.get,set:function(value){
    var target=value;try{var u=new URL(String(value),original.href);if(this.crossOrigin!==null&&u.origin===original.origin&&/\.(png|jpe?g|webp|gif)$/i.test(u.pathname)){target=route(u.href,'GET');this.crossOrigin='use-credentials';}}catch(e){}
    return imageSrc.set.call(this,target);
}});
function route(value,method){
    var url=new URL(String(value),original.href);
    if(url.origin===endpoint.origin && url.searchParams.get('controller')!=='proxyajax')url=new URL(url.pathname+url.search+url.hash,original.origin);
    if(url.origin!==original.origin)return null;
    if(String(method||'GET').toUpperCase()!=='GET')throw new Error('Podgląd obsługuje tylko odczyt GET.');
    url.searchParams.delete('csrfKey');url.searchParams.delete('csrf');url.searchParams.delete('rating_submitted');
    var gateway=new URL(endpoint.href);gateway.searchParams.set('cap',config.token);gateway.searchParams.set('target',url.href);if(/\.(css|woff2?|m?js|glb|png|jpe?g|webp|gif)$/i.test(url.pathname))gateway.searchParams.set('asset','1');return gateway.href;
}
var nativeFetch=window.fetch;
if(nativeFetch)window.fetch=function(input,init){// Ravia bulk-preloads all media before mounting React. In the proxy, let native
// image/video elements load from Ravia instead; never relay the entire video library.
var requested=new URL(input instanceof Request?input.url:String(input),original.href);
if(original.hostname==='ravia.pl' && original.pathname==='/prezentacja/' && requested.pathname==='/prezentacja/preload-manifest.json' && (requested.origin===original.origin||requested.origin===endpoint.origin) && String((init||{}).method||'GET').toUpperCase()==='GET')return Promise.resolve(new Response(JSON.stringify({assets:[],totalBytes:0}),{status:200,headers:{'Content-Type':'application/json'}}));
init=init||{};var isRequest=input instanceof Request,method=init.method||(isRequest?input.method:'GET'),target=route(isRequest?input.url:input,method);if(!target)return nativeFetch.call(window,input,init);return nativeFetch.call(window,target,{method:'GET',credentials:'include',signal:init.signal||(isRequest?input.signal:undefined)});};
var open=XMLHttpRequest.prototype.open,send=XMLHttpRequest.prototype.send,setHeader=XMLHttpRequest.prototype.setRequestHeader;
XMLHttpRequest.prototype.open=function(method,url,async){var target=route(url,method);this.__mpcsProxy=!!target;if(target)return open.call(this,'GET',target,async!==false);return open.apply(this,arguments);};
XMLHttpRequest.prototype.setRequestHeader=function(name,value){if(this.__mpcsProxy)return;return setHeader.call(this,name,value);};
XMLHttpRequest.prototype.send=function(body){if(this.__mpcsProxy){this.withCredentials=true;return send.call(this,null);}return send.call(this,body);};
// Keep section links local and prevent client-side routers from navigating to IPS routes.
function sectionFor(text){var norm=function(s){return s.trim().toLocaleLowerCase();},nodes=document.querySelectorAll('section h1,section h2,section h3');for(var i=0;i<nodes.length;i++)if(norm(nodes[i].textContent)===norm(text))return nodes[i].closest('section');return null;}
document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a');if(!a||a.hasAttribute('data-slideid'))return;var raw=a.getAttribute('href'),section;if(!raw){section=sectionFor(a.textContent);if(section){e.preventDefault();e.stopImmediatePropagation();section.scrollIntoView({behavior:'smooth',block:'start'});}return;}var u;try{u=new URL(raw,original.href);}catch(x){return;}if(u.hash&&(u.origin===original.origin||u.origin===endpoint.origin)){section=document.getElementById(decodeURIComponent(u.hash.slice(1)));if(section){e.preventDefault();e.stopImmediatePropagation();section.scrollIntoView({behavior:'smooth',block:'start'});return;}}if(u.protocol==='https:'){e.preventDefault();e.stopImmediatePropagation();if(u.origin===endpoint.origin)u=new URL(u.pathname+u.search+u.hash,original.origin);window.open(u.href,'_blank','noopener,noreferrer');}},true);
// Routers sometimes attempt a full navigation after a failed RSC request.
if(window.navigation)window.navigation.addEventListener('navigate',function(e){var u=new URL(e.destination.url);if(e.cancelable&&u.origin===endpoint.origin&&u.pathname+u.search!==location.pathname+location.search){e.preventDefault();if(u.hash){var node=document.getElementById(decodeURIComponent(u.hash.slice(1)));if(node)node.scrollIntoView({behavior:'smooth'});}}});
function mirrorStyles(){document.querySelectorAll('link[rel="stylesheet"],link[as="font"]').forEach(function(link){var u;try{u=new URL(link.href,original.href);}catch(e){return;}if(u.origin!==original.origin||!(/\.(css|woff2?)$/i).test(u.pathname))return;var g=new URL(endpoint.href);g.searchParams.set('asset','1');g.searchParams.set('cap',config.token);g.searchParams.set('target',u.href);link.href=g.href;});}
new MutationObserver(mirrorStyles).observe(document.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['href']});mirrorStyles();
// Next App Router uses the iframe URL as its application origin. Keep same-page
// hash updates inside the presentation instead of requesting the forum root.
function adaptRouter(router){if(!router||router.__mpcsAdapted)return router;['push','replace'].forEach(function(method){if(typeof router[method]!=='function')return;router[method]=function(value){var u=new URL(String(value),original.href);if(u.protocol!=='https:')return;if(u.origin===original.origin&&u.pathname===original.pathname)return;window.open(u.href,'_blank','noopener,noreferrer');};});router.__mpcsAdapted=true;return router;}
function adaptNext(next){if(!next||typeof next!=='object')return next;var router=adaptRouter(next.router);try{Object.defineProperty(next,'router',{configurable:true,enumerable:true,get:function(){return router;},set:function(value){router=adaptRouter(value);}});}catch(e){}return next;}
var nextValue=adaptNext(window.next);try{Object.defineProperty(window,'next',{configurable:true,get:function(){return nextValue;},set:function(value){nextValue=adaptNext(value);}});}catch(e){}
function mirrorMedia(){document.querySelectorAll('img,source,video').forEach(function(el){['src','poster'].forEach(function(attr){var value=el.getAttribute(attr);if(!value||/^(data:|blob:)/i.test(value))return;try{var u=new URL(value,original.href);if(u.origin===endpoint.origin&&u.searchParams.get('controller')==='proxyajax'&&u.searchParams.get('cap')===config.token)return;if(u.origin===endpoint.origin)u=new URL(u.pathname+u.search+u.hash,original.origin);if(value!==u.href)el.setAttribute(attr,u.href);}catch(e){}});var list=el.getAttribute('srcset');if(list){var rewritten=list.split(',').map(function(item){var parts=item.trim().split(/\s+/);try{var u=new URL(parts[0],original.href);if(u.origin===endpoint.origin)u=new URL(u.pathname+u.search,original.origin);parts[0]=u.href;}catch(e){}return parts.join(' ');}).join(', ');if(rewritten!==list)el.setAttribute('srcset',rewritten);}});}
new MutationObserver(mirrorMedia).observe(document.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['src','srcset','poster']});mirrorMedia();
