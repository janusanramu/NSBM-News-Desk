/* A smooth entry on authenticated pages, including page refreshes. */
(function () {
  'use strict';
  var loader = document.getElementById('nsbm-page-preloader');
  if (!loader) return;
  var start = performance.now();
  var finished = false;
  function finish() {
    if (finished) return;
    finished = true;
    // Let the logo animation be visible without keeping users waiting unnecessarily.
    var delay = Math.max(0, 900 - (performance.now() - start));
    window.setTimeout(function () {
      loader.classList.add('is-hidden');
      window.setTimeout(function () { if (loader.parentNode) loader.remove(); }, 650);
    }, delay);
  }
  if (document.readyState === 'complete') finish();
  else window.addEventListener('load', finish, { once: true });
  // Images or third-party scripts should never leave the page hidden indefinitely.
  window.setTimeout(finish, 4200);
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) { loader.remove(); }
  });
})();
