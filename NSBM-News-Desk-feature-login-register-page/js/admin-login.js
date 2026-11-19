document.addEventListener('DOMContentLoaded', () => {
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
});
