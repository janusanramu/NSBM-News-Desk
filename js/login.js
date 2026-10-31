/* NSBM NewsDesk — standalone login page interactions */
document.addEventListener('DOMContentLoaded', () => {
  const $ = (selector) => document.querySelector(selector);
  const menu = $('.menu-toggle');
  const nav = $('.main-nav');
  menu?.addEventListener('click', () => {
    const open = nav?.classList.toggle('is-open') ?? false;
    menu.setAttribute('aria-expanded', String(open));
  });

  const themeButton = $('.theme-toggle');
  themeButton?.addEventListener('click', () => {
    const light = document.body.classList.toggle('theme-light');
    document.body.classList.toggle('theme-dark', !light);
    themeButton.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
    const icon = themeButton.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = light ? 'dark_mode' : 'light_mode';
  });

  const email = $('#email');
  const password = $('#password');
  document.querySelectorAll('.fill-btn[data-email]').forEach(button => {
    button.addEventListener('click', () => {
      if (email) { email.value = button.dataset.email || ''; email.focus(); email.dispatchEvent(new Event('input', { bubbles: true })); }
    });
  });
  document.querySelectorAll('.toggle-pass').forEach(button => {
    button.addEventListener('click', () => {
      const field = document.getElementById(button.dataset.target || '');
      if (!field) return;
      const show = field.type === 'password';
      field.type = show ? 'text' : 'password';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      const icon = button.querySelector('.material-symbols-outlined');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
    });
  });

  const form = $('#loginForm');
  function setError(input, message) {
    const wrapper = input?.closest('.field');
    if (!wrapper) return;
    wrapper.classList.toggle('has-error', Boolean(message));
    const output = wrapper.querySelector('.error-msg');
    if (output) output.textContent = message;
    input.setAttribute('aria-invalid', String(Boolean(message)));
  }
  [email, password].forEach(input => input?.addEventListener('input', () => setError(input, '')));
  form?.addEventListener('submit', event => {
    let valid = true;
    if (!email?.value.trim() || !email.checkValidity()) { setError(email, 'Please enter a valid email address.'); valid = false; }
    if (!password?.value) { setError(password, 'Please enter your password.'); valid = false; }
    if (!valid) { event.preventDefault(); form.querySelector('[aria-invalid="true"]')?.focus(); }
    // Valid forms submit to the existing login.php backend; no fake login is performed.
  });

  const ticker = $('#tickerInner');
  if (ticker && !ticker.textContent.trim()) {
    ticker.textContent = 'Welcome to NSBM NewsDesk  •  Your campus. Your voice.  •  Discover stories and stay connected.';
  }

  // Continuous logo playback with gentle pointer-responsive movement.
  const video = document.querySelector('.mouse-scrub-video');
  const panel = video?.closest('.feature-video');
  if (video && panel) {
    video.muted = true;
    video.loop = true;
    const startPlayback = () => video.play().catch(() => {});
    video.addEventListener('canplay', startPlayback, { once: true });
    startPlayback();
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    panel.addEventListener('pointermove', event => {
      if (reducedMotion.matches || event.pointerType === 'touch') return;
      const rect = panel.getBoundingClientRect();
      const x = (event.clientX - rect.left) / rect.width - 0.5;
      const y = (event.clientY - rect.top) / rect.height - 0.5;
      video.style.transform = `translate(${x * 12}px, ${y * 12}px) scale(1.025)`;
    });
    panel.addEventListener('pointerleave', () => { video.style.transform = ''; });
  }
});
