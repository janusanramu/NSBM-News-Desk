/* NSBM NewsDesk — Community Guidelines page only */
document.addEventListener('DOMContentLoaded', () => {
  // 01. Breaking news ticker
  const ticker = document.getElementById('tickerInner');
  if (ticker) {
    ticker.textContent = 'Welcome to NSBM NewsDesk • Discover campus stories • Share your voice • Stay connected • ';
  }

  // 02. Mobile navigation
  const menuButton = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('.main-nav');
  menuButton?.addEventListener('click', () => {
    const isOpen = navigation?.classList.toggle('is-open') ?? false;
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    const icon = menuButton.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = isOpen ? 'close' : 'menu';
  });

  // 03. Theme switching (only on this page)
  const themeButton = document.querySelector('.theme-toggle');
  themeButton?.addEventListener('click', () => {
    const light = document.body.classList.toggle('theme-light');
    document.body.classList.toggle('theme-dark', !light);
    themeButton.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
    const icon = themeButton.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = light ? 'dark_mode' : 'light_mode';
  });
});
