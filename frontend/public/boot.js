// Recovers a tab holding an index.html from a previous deploy: its hashed
// bundle no longer exists, so the script fails to load and the page stays
// blank. Reload once to fetch the current index.html. Kept as an external
// file because the Content-Security-Policy forbids inline scripts.
(function () {
  var KEY = 'vr-stale-reload';
  window.addEventListener(
    'error',
    function (event) {
      var el = event.target;
      if (!el || el.tagName !== 'SCRIPT' || !/\/assets\//.test(el.src || '')) return;
      try {
        if (sessionStorage.getItem(KEY)) return; // already retried: avoid a reload loop
        sessionStorage.setItem(KEY, '1');
      } catch (e) {
        return;
      }
      window.location.reload();
    },
    true,
  );
  window.addEventListener('load', function () {
    try {
      sessionStorage.removeItem(KEY);
    } catch (e) {
      /* storage unavailable: nothing to clear */
    }
  });
})();
