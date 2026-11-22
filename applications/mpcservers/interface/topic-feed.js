(function () {
    'use strict';
    if (window.mpcPublicationFeedMeta) return;
    window.mpcPublicationFeedMeta = true;
    var scheduled = false;
    function visible(node) {
        return node && node.getClientRects().length > 0 && getComputedStyle(node).visibility !== 'hidden';
    }
    function identity(node) {
        var author = node.querySelector('a.ipsUsername'), time = node.querySelector('time[datetime]');
        if (!author || !time) return null;
        var url;
        try { url = new URL(author.href, document.baseURI); } catch (e) { return null; }
        var date = Date.parse(time.getAttribute('datetime'));
        if (!Number.isFinite(date)) return null;
        return url.origin + url.pathname.replace(/\/$/, '') + '|' + date;
    }
    function update() {
        scheduled = false;
        document.querySelectorAll('.ipsData--topic-feed-widget > .ipsData__item').forEach(function (row) {
            var publication = row.querySelector('.mpcs-b, .sps-recommended');
            var meta = row.querySelector('.ipsData__main > .ipsData__meta');
            var key = publication && visible(meta) ? identity(meta) : null;
            row.querySelectorAll('.ipsData__extra > .ipsData__last').forEach(function (last) {
                var duplicate = !!key && identity(last) === key;
                if (duplicate && !last.hasAttribute('data-mpcs-duplicate-meta')) last.setAttribute('data-mpcs-duplicate-meta', '');
                else if (!duplicate && last.hasAttribute('data-mpcs-duplicate-meta')) last.removeAttribute('data-mpcs-duplicate-meta');
            });
        });
    }
    function schedule() { if (!scheduled) { scheduled = true; requestAnimationFrame(update); } }
    function init() {
        update();
        new MutationObserver(function (changes) {
            if (changes.some(function (change) {
                return change.target.nodeType === 1 && (change.target.closest('.ipsData--topic-feed-widget') ||
                    Array.from(change.addedNodes).some(function (node) { return node.nodeType === 1 && (node.matches('.ipsData--topic-feed-widget') || node.querySelector('.ipsData--topic-feed-widget')); }));
            })) schedule();
        }).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style', 'hidden', 'href', 'datetime'] });
        window.addEventListener('resize', schedule, { passive: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
