/* Breaking ticker: shows the newest published articles (including ones the admin just posted).
   It reads the same public "published" feed the news pages use, so the ticker always matches the news below. */
(function () {
  'use strict';
  var text = document.querySelector('.breaking-text');
  if (!text) return;

  var base = document.body.getAttribute('data-base') || '';
  var apiUrl = base.charAt(0) === '/' ? new URL(base + 'api.php', location.origin) : new URL('api.php', location.href);
  var articleBase = base.charAt(0) === '/' ? base : '';
  var fallback = text.innerHTML;
  var lastSignature = '';
  var MAX_ITEMS = 5;
  var REFRESH_MS = 45000;

  function esc(value) {
    return String(value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function render(articles) {
    var signature = articles.map(function (a) { return a.id + ':' + a.title; }).join('|');
    if (signature === lastSignature) return;
    lastSignature = signature;

    if (!articles.length) {
      text.innerHTML = fallback;
      text.style.animationDuration = '';
      return;
    }

    var dayAgo = Date.now() - 24 * 60 * 60 * 1000;
    text.innerHTML = articles.map(function (a) {
      var when = Date.parse(String(a.published_at || a.created_at || '').replace(' ', 'T'));
      var badge = when && when > dayAgo ? '<span class="breaking-new">NEW</span>' : '';
      return '<a class="breaking-item" href="' + esc(articleBase + 'article.php?id=' + encodeURIComponent(a.id)) + '">' + badge + esc(a.title) + '</a>';
    }).join('<span class="breaking-sep" aria-hidden="true">&bull;</span>');

    /* About 70px per second, never faster than 30s for one loop. */
    text.style.animationDuration = Math.max(30, Math.round(text.scrollWidth / 70)) + 's';
  }

  function refresh() {
    return fetch(apiUrl.toString() + '?action=published', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
      .then(function (data) { render((data.articles || []).slice(0, MAX_ITEMS)); })
      .catch(function () { /* keep whatever is already showing */ });
  }

  window.NSBMBreaking = { refresh: refresh };
  refresh();
  setInterval(refresh, REFRESH_MS);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
})();
