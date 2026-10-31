/* NSBM NewsDesk | Register page only */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('registerForm');
  if (!form) return;

  // 01. Breaking-news ticker placeholder (replace with real headlines later)
  const ticker = document.getElementById('tickerInner');
  if (ticker) ticker.textContent = 'Welcome to NSBM NewsDesk  •  Campus news and stories  •  Share your voice  •  Join the community  •';

  // 02. Mobile navigation
  const menu = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  menu?.addEventListener('click', () => {
    const opened = nav?.classList.toggle('is-open') ?? false;
    menu.setAttribute('aria-expanded', String(opened));
    menu.setAttribute('aria-label', opened ? 'Close navigation' : 'Open navigation');
  });

  // 03. Theme toggle
  const theme = document.querySelector('.theme-toggle');
  theme?.addEventListener('click', () => {
    const light = document.body.classList.toggle('theme-light');
    document.body.classList.toggle('theme-dark', !light);
    const icon = theme.querySelector('.material-symbols-outlined');
    if (icon) icon.textContent = light ? 'dark_mode' : 'light_mode';
    theme.setAttribute('aria-label', light ? 'Switch to dark theme' : 'Switch to light theme');
  });

  // 04. Show / hide passwords
  document.querySelectorAll('#registerForm .toggle-pass').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      const icon = button.querySelector('.material-symbols-outlined');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
    });
  });

  // 05. Password strength meter
  const password = document.getElementById('password');
  const strengthBar = document.getElementById('strengthBar');
  const strengthLabel = document.getElementById('strengthLabel');
  function strengthScore(value) {
    return [value.length >= 8, /[a-z]/.test(value), /[A-Z]/.test(value), /\d/.test(value), /[^A-Za-z0-9]/.test(value)].filter(Boolean).length;
  }
  password?.addEventListener('input', () => {
    const value = password.value;
    const score = value ? strengthScore(value) : 0;
    if (strengthBar) {
      strengthBar.style.width = `${score * 20}%`;
      strengthBar.style.background = score <= 2 ? '#d85050' : score <= 3 ? '#e0a72d' : '#0d9b68';
    }
    if (strengthLabel) strengthLabel.textContent = !value ? 'Use 8+ characters with upper/lowercase, numbers and symbols.' : score <= 2 ? 'Weak password' : score <= 3 ? 'Fair password' : score === 4 ? 'Good password' : 'Strong password';
  });

  // 06. Form validation; successful submissions continue to register.php
  function showError(input, message) {
    const field = input.closest('.field');
    if (!field) return;
    field.classList.toggle('has-error', Boolean(message));
    input.setAttribute('aria-invalid', String(Boolean(message)));
    const error = field.querySelector('.error-msg');
    if (error) error.textContent = message;
  }
  function checkInput(input) {
    const value = input.value.trim();
    let message = '';
    if (!value) message = 'This field is required.';
    else if (input.id === 'fullname' && value.length < 2) message = 'Enter your full name.';
    else if (input.id === 'email' && !/^[^\s@]+@nsbm\.ac\.lk$/i.test(value)) message = 'Use a valid @nsbm.ac.lk email address.';
    else if (input.id === 'password' && value.length < 8) message = 'Password must contain at least 8 characters.';
    else if (input.id === 'confirm' && value !== password.value) message = 'Passwords do not match.';
    showError(input, message);
    return !message;
  }
  const fields = ['fullname', 'email', 'faculty', 'password', 'confirm'].map(id => document.getElementById(id)).filter(Boolean);
  fields.forEach(input => {
    input.addEventListener('blur', () => checkInput(input));
    input.addEventListener('input', () => { if (input.closest('.field')?.classList.contains('has-error')) checkInput(input); });
    input.addEventListener('change', () => checkInput(input));
  });
  form.addEventListener('submit', event => {
    let valid = true;
    fields.forEach(input => { if (!checkInput(input)) valid = false; });
    const terms = document.getElementById('terms');
    const termsError = document.querySelector('#termsField .error-msg');
    if (termsError) {
      termsError.textContent = terms?.checked ? '' : 'Please agree to the Community Guidelines and Terms of Service.';
      termsError.style.display = terms?.checked ? 'none' : 'block';
    }
    if (!terms?.checked) valid = false;
    if (!valid) {
      event.preventDefault();
      form.querySelector('[aria-invalid="true"]')?.focus();
      if (fields.every(input => input.getAttribute('aria-invalid') !== 'true')) terms?.focus();
    }
  });
  document.getElementById('terms')?.addEventListener('change', () => {
    const error = document.querySelector('#termsField .error-msg');
    if (error) error.style.display = 'none';
  });

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
