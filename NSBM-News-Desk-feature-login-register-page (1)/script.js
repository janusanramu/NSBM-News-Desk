/* ===== NSBM NewsDesk – Login & Register scripts ===== */
document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Breaking news ticker ---------- */
  const headlines = [
    'Annual Green Walk 2024 announced with massive participation from all faculties',
    'Faculty of Computing wins the Inter-Faculty Hackathon 2024',
    'Admissions for the new intake are now open'
  ];
  const tickerInner = document.getElementById('tickerInner');
  if (tickerInner) {
    const line = headlines.join(' \u2022 ') + ' \u2022 ';
    tickerInner.textContent = line + line; // duplicated for seamless loop
  }

  /* ---------- Helpers ---------- */
  const EMAIL_RE = /^[^\s@]+@nsbm\.ac\.lk$/i;

  const setError = (input, message) => {
    const field = input.closest('.field');
    if (!field) return;
    field.classList.add('has-error');
    input.closest('.input-wrap')?.classList.add('error');
    const msg = field.querySelector('.error-msg');
    if (msg) msg.textContent = message;
  };
  const clearError = (input) => {
    const field = input.closest('.field');
    if (!field) return;
    field.classList.remove('has-error');
    input.closest('.input-wrap')?.classList.remove('error');
    const msg = field.querySelector('.error-msg');
    if (msg) msg.textContent = '';
  };
  document.querySelectorAll('input,select').forEach(el =>
    el.addEventListener('input', () => clearError(el)));

  /* ---------- Login page ---------- */
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    // Demo "Fill" buttons  (change DEMO_PASSWORD to match your database)
    const DEMO_PASSWORD = 'Password@123';
    document.querySelectorAll('.fill-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('email').value = btn.dataset.email;
        document.getElementById('password').value = DEMO_PASSWORD;
        clearError(document.getElementById('email'));
        clearError(document.getElementById('password'));
      });
    });

    // Show / hide password
    const toggle = loginForm.querySelector('.toggle-pass');
    toggle.addEventListener('click', () => {
      const pw = document.getElementById('password');
      const icon = toggle.querySelector('.material-symbols-outlined');
      const show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      icon.textContent = show ? 'visibility_off' : 'visibility';
    });

    loginForm.addEventListener('submit', (e) => {
      let ok = true;
      const email = document.getElementById('email');
      const pw = document.getElementById('password');
      if (!EMAIL_RE.test(email.value.trim())) {
        setError(email, 'Enter a valid @nsbm.ac.lk email address.'); ok = false;
      }
      if (!pw.value) { setError(pw, 'Enter your password.'); ok = false; }
      if (!ok) e.preventDefault();
    });
  }

  /* ---------- Register page ---------- */
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      let ok = true;
      const name = document.getElementById('fullname');
      const email = document.getElementById('email');
      const faculty = document.getElementById('faculty');
      const pw = document.getElementById('password');
      const confirm = document.getElementById('confirm');
      const terms = document.getElementById('terms');
      const termsMsg = document.querySelector('#termsField .error-msg');
      termsMsg.textContent = '';

      if (name.value.trim().length < 3) { setError(name, 'Enter your full name.'); ok = false; }
      if (!EMAIL_RE.test(email.value.trim())) {
        setError(email, 'Use your @nsbm.ac.lk email address.'); ok = false;
      }
      if (!faculty.value) { setError(faculty, 'Select your faculty.'); ok = false; }
      if (pw.value.length < 8) { setError(pw, 'Password must be at least 8 characters.'); ok = false; }
      if (confirm.value !== pw.value || !confirm.value) {
        setError(confirm, 'Passwords do not match.'); ok = false;
      }
      if (!terms.checked) {
        termsMsg.textContent = 'You must accept the guidelines and terms.'; ok = false;
      }
      if (!ok) e.preventDefault();
    });
  }
});
