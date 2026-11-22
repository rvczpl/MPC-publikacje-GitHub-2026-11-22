(function () {
    'use strict';
    if (window.mpcTopicTitlePrefixes) return;
    window.mpcTopicTitlePrefixes = true;
    var scheduled = false;
    var style = document.createElement('style');
    style.textContent = '.ipsPageHeader__title > .mpcs-title-after{order:1;display:inline-flex!important;flex-wrap:wrap;gap:4px;align-items:center;margin-left:8px;max-width:100%;min-width:0}.ipsPageHeader__title:has(>.mpcs-title-after)>h1{order:0}.ipsPageHeader__title>.mpcs-title-prefixes{display:inline-flex!important}.sps-top-award{background:#6750a4;color:#fff}';
    document.head.appendChild(style);
    function update() {
        scheduled = false;
        document.querySelectorAll('.ipsPageHeader__title').forEach(function (header) {
            var title = header.querySelector(':scope > h1[data-ips-hook="title"], :scope > h1[data-ips-hook="titleEditable"]');
            var badges = header.querySelector(':scope > [data-ips-hook="badges"]');
            if (!title || !badges) return;
            var prefixes = Array.from(badges.children).filter(function (badge) { return badge.matches('.mpcs-b, .sps-recommended, .sps-top-award'); });
            if (!prefixes.length) { if (badges.children.length) badges.hidden = false; return; }
            var group = header.querySelector(':scope > [data-mpcs-title-prefixes]');
            if (!group) {
                group = document.createElement('div');
                group.className = 'ipsBadges mpcs-title-prefixes';
                group.setAttribute('data-mpcs-title-prefixes', '');
                header.insertBefore(group, title);
            }
            var versions = header.querySelector(':scope > [data-mpcs-title-after]');
            if (!versions) {
                versions = document.createElement('div');
                versions.className = 'ipsBadges mpcs-title-after';
                versions.setAttribute('data-mpcs-title-after', '');
                title.after(versions);
            }
            // Refreshes replace native badge contents. Move nodes without copying IDs or event handlers.
            function rank(badge) { return badge.classList.contains('mpcs-version') ? 0 : (badge.matches('.sps-recommended,.sps-top-award') ? 1 : (badge.dataset.mpcsRotateGroup === 'server-modes' ? 2 : 3)); }
            function beforeTitle(badge) { return badge.matches('.mpcs-version,.sps-recommended,.sps-top-award'); }
            prefixes.sort(function (a,b) { return rank(a)-rank(b); });
            group.replaceChildren.apply(group, prefixes.filter(function (badge) { return beforeTitle(badge); }));
            versions.replaceChildren.apply(versions, prefixes.filter(function (badge) { return !beforeTitle(badge); }));
            badges.hidden = badges.children.length === 0;
        });
    }
    function schedule() { if (!scheduled) { scheduled = true; requestAnimationFrame(update); } }
    function init() {
        update();
        new MutationObserver(function (changes) {
            if (changes.some(function (change) {
                return change.target.nodeType === 1 && (change.target.closest('.ipsPageHeader__title') || Array.from(change.addedNodes).some(function (node) {
                    return node.nodeType === 1 && (node.matches('.ipsPageHeader__title') || node.querySelector('.ipsPageHeader__title'));
                }));
            })) schedule();
        }).observe(document.body, { subtree: true, childList: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
