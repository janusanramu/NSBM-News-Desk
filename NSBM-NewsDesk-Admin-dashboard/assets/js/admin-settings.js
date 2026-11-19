/* Administrator settings presentation. Data is saved by app/admin-live.js. */
(() => { 'use strict';
 const $=id=>document.getElementById(id);const form=$('adminSettingsForm');if(!form)return;
 const feedback=(message,error=false)=>{const el=$('asFeedback');if(el){el.textContent=message;el.classList.toggle('error',error);}};
 // Theme changes are controlled by theme.js to avoid duplicate click handlers.
 const theme=()=>{if(window.NSBMAdminTheme)window.NSBMAdminTheme.applyTheme($('asTheme').value);
  document.body.classList.toggle('as-compact',$('asCompact').checked);
 };
 const strength=()=>{const v=$('asNew').value;const score=[v.length>=12,/[A-Z]/.test(v)&&/[a-z]/.test(v),/\d/.test(v),/[^A-Za-z0-9]/.test(v)].filter(Boolean).length;
  $('asStrengthBar').style.width=v?score*25+'%':'0';$('asStrengthLabel').textContent=v?['Very weak','Weak','Fair','Strong','Very strong'][score]:'Enter a new password to check its strength';};
 document.querySelectorAll('[data-settings-tab]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('[data-settings-tab]').forEach(b=>b.classList.toggle('active',b===btn));document.querySelectorAll('[data-settings-pane]').forEach(el=>el.hidden=el.dataset.settingsPane!==btn.dataset.settingsTab);feedback('');}));
 document.querySelectorAll('.as-eye').forEach(btn=>btn.addEventListener('click',()=>{const field=btn.parentElement.querySelector('input');field.type=field.type==='password'?'text':'password';btn.setAttribute('aria-label',field.type==='password'?'Show password':'Hide password');}));
 $('asNew')?.addEventListener('input',strength);$('asCompact')?.addEventListener('change',()=>document.body.classList.toggle('as-compact',$('asCompact').checked));
 $('asName')?.addEventListener('input',()=>{const t=$('asName').value.trim().split(/\s+/).slice(0,2).map(x=>x[0]||'').join('').toUpperCase();$('asInitials').textContent=t||'AD';});
 strength();document.body.classList.toggle('as-compact',$('asCompact').checked);
})();
