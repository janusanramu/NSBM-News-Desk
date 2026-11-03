// Drop-in replacement for the dashboard's admin-settings.js.
(() => {
  const form=document.getElementById('adminSettingsForm'); if(!form)return;
  const el=id=>document.getElementById(id);
  const ids=['asName','asEmail','asPhone','asTheme','asCompact','asNotifyEmail','asNotifyArticles','asNotifyComments','asNotifyAnnouncements'];
  let csrf='', saved={}, photo='', removePhoto=false;
  const feedback=(msg,error=false)=>{el('asFeedback').textContent=msg;el('asFeedback').classList.toggle('error',error);};
  const applyTheme=()=>{document.body.classList.toggle('as-dark',el('asTheme').value==='dark'||(el('asTheme').value==='system'&&matchMedia('(prefers-color-scheme: dark)').matches));document.body.classList.toggle('as-compact',el('asCompact').checked);};
  const showPhoto=()=>{const img=el('asPhotoPreview');img.hidden=!photo;if(photo)img.src=photo;else img.removeAttribute('src');el('asInitials').hidden=!!photo;};
  const strength=()=>{const p=el('asNew').value;let s=0;if(p.length>=12)s++;if(/[A-Z]/.test(p)&&/[a-z]/.test(p))s++;if(/\d/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;el('asStrengthBar').style.width=p?s*25+'%':'0';el('asStrengthBar').style.background=s>=3?'#078657':s===2?'#d9a332':'#d35d51';el('asStrengthLabel').textContent=p?['Very weak','Weak','Fair','Strong','Very strong'][s]:'Enter a new password to check its strength';};
  const apply=()=>{ids.forEach(id=>{const n=el(id);if(n.type==='checkbox')n.checked=!!saved[id];else n.value=saved[id]??'';});photo=saved.photo||'';removePhoto=false;showPhoto();['asCurrent','asNew','asConfirm'].forEach(id=>el(id).value='');strength();applyTheme();};
  const request=async (data,method='POST')=>{const opts={method,credentials:'same-origin',headers:{'X-CSRF-Token':csrf}};if(data)opts.body=data;const res=await fetch('api_admin_settings.php',opts);const json=await res.json();if(!res.ok)throw Error(json.error||'Request failed.');return json;};
  async function load(){try{const j=await request(null,'GET');csrf=j.csrf;saved=j.values;apply();feedback('');}catch(e){feedback(e.message,true);}}
  document.querySelectorAll('[data-settings-tab]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-settings-tab]').forEach(x=>x.classList.toggle('active',x===b));document.querySelectorAll('[data-settings-pane]').forEach(p=>p.hidden=p.dataset.settingsPane!==b.dataset.settingsTab);feedback('');}));
  document.querySelectorAll('.as-eye').forEach(b=>b.addEventListener('click',()=>{const n=b.parentElement.querySelector('input');n.type=n.type==='password'?'text':'password';b.setAttribute('aria-label',n.type==='password'?'Show password':'Hide password');}));
  el('asNew').addEventListener('input',strength);el('asTheme').addEventListener('change',applyTheme);el('asCompact').addEventListener('change',applyTheme);
  el('asName').addEventListener('input',()=>{el('asInitials').textContent=el('asName').value.trim().split(/\s+/).slice(0,2).map(x=>x[0]||'').join('').toUpperCase()||'AD';});
  el('asPhoto').addEventListener('change',()=>{const f=el('asPhoto').files[0];if(!f)return;if(!['image/jpeg','image/png','image/webp'].includes(f.type)||f.size>2097152){feedback('Select a JPG, PNG or WebP under 2 MB.',true);el('asPhoto').value='';return;}const r=new FileReader();r.onload=()=>{photo=r.result;removePhoto=false;showPhoto();feedback('Photo selected. Click Save Changes.');};r.readAsDataURL(f);});
  el('asRemovePhoto').addEventListener('click',()=>{removePhoto=true;photo='';el('asPhoto').value='';showPhoto();feedback('Photo will be removed when you save.');});
  el('asReset').addEventListener('click',()=>{apply();feedback('Changes cancelled.');});
  form.addEventListener('submit',async e=>{e.preventDefault();const submit=form.querySelector('[type="submit"]');submit.disabled=true;try{
    const current=el('asCurrent').value,newPass=el('asNew').value,confirm=el('asConfirm').value;
    if(current||newPass||confirm){if(!current||newPass.length<12||newPass!==confirm)throw Error('Enter current password and matching new password (at least 12 characters).');const d=new FormData();d.set('action','password');d.set('current',current);d.set('new',newPass);d.set('confirm',confirm);await request(d);['asCurrent','asNew','asConfirm'].forEach(id=>el(id).value='');strength();}
    const d=new FormData();d.set('action','save');ids.forEach(id=>{const n=el(id);d.set(id,n.type==='checkbox'?(n.checked?'1':'0'):n.value);});const j=await request(d);saved=j.values;
    if(removePhoto){const p=new FormData();p.set('action','remove_photo');saved=(await request(p)).values;}
    else if(el('asPhoto').files[0]){const p=new FormData();p.set('action','photo');p.set('photo',el('asPhoto').files[0]);saved=(await request(p)).values;}
    el('asPhoto').value='';apply();feedback('Settings saved successfully.');
  }catch(err){feedback(err.message,true);}finally{submit.disabled=false;}});
  load();
})();
