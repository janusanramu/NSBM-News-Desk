// Single theme controller for the Admin Dashboard and Admin Profile Settings.
(() => {
  'use strict';
  const root = document.documentElement;
  const toggle = document.getElementById('themeToggle');
  const select = document.getElementById('asTheme');
  const label = document.getElementById('themeLabel');
  const icon = document.getElementById('themeIcon');
  const storageKey = 'nsbmNewsDeskTheme';
  const isValid = value => ['light', 'dark', 'system'].includes(value);
  const resolve = value => value === 'system'
    ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
    : value;
  function applyTheme(value, persist = true) {
    const choice = isValid(value) ? value : 'light';
    const resolved = resolve(choice);
    root.dataset.theme = resolved;
    if (select) select.value = choice;
    if (label) label.textContent = resolved === 'dark' ? 'Dark mode' : 'Light mode';
    if (icon) icon.className = resolved === 'dark' ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
    if (toggle) {
      toggle.setAttribute('aria-pressed', String(resolved === 'dark'));
      toggle.setAttribute('aria-label', resolved === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    }
    if (persist) {
      try { localStorage.setItem(storageKey, choice); } catch (_) {}
    }
    // Keep reports' chart colors matched to the active theme.
    if (typeof window.renderReports === 'function' &&
        document.getElementById('reportsPage') &&
        !document.getElementById('reportsPage').classList.contains('hidden')) {
      window.renderReports();
    }
    return resolved;
  }
  let saved = 'light';
  try { saved = localStorage.getItem(storageKey) || 'light'; } catch (_) {}
  applyTheme(saved, false);
  if (toggle) toggle.addEventListener('click', () => {
    applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
  });
  if (select) select.addEventListener('change', () => applyTheme(select.value));
  if (window.matchMedia) {
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const refreshSystem = () => {
      if (select && select.value === 'system') applyTheme('system', false);
    };
    if (media.addEventListener) media.addEventListener('change', refreshSystem);
    else if (media.addListener) media.addListener(refreshSystem);
  }
  window.NSBMAdminTheme = { applyTheme, getChoice: () => select ? select.value : root.dataset.theme };
})();
