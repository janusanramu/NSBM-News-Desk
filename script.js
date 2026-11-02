// Standalone dashboard preview. Connect these tabs to your PHP pages when ready.
const tabs = document.querySelectorAll('.tab');
const pageRoutes = {
  // submissions: 'my-submissions.php',
  // bookmarks: 'saved-bookmarks.php',
  // submit: 'submit-article.php',
  // settings: 'account-settings.php'
};
const overview = document.getElementById('overview');
const subscription = document.getElementById('subscription');
tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    const target = tab.dataset.tab;
    if (pageRoutes[target]) { window.location.href = pageRoutes[target]; return; }
    if (target !== 'overview' && target !== 'subscription' && target !== 'settings') return;
    const showingSubscription = target === 'subscription';
    overview.hidden = target !== 'overview';
    subscription.hidden = !showingSubscription;
    document.getElementById('settings').hidden = target !== 'settings';
    tabs.forEach(t => {
      const active = t === tab;
      t.classList.toggle('active', active);
      if (active) t.setAttribute('aria-current', 'page');
      else t.removeAttribute('aria-current');
      if (t.dataset.tab === 'subscription') t.setAttribute('aria-expanded', String(showingSubscription));
    });
  });
});

// Categories and subscriptions are loaded from the authenticated PHP API.
let categories = [], followed = {};
const list = document.getElementById('subscription-list');
const search = document.getElementById('category-search');
const feedback = document.getElementById('subscription-feedback');
async function api(action, data, file = false) {
  const options = {credentials:'same-origin'};
  if (data !== undefined) {
    options.method = 'POST';
    options.headers = {'X-CSRF-Token': window.NEWS_DESK_CSRF};
    if (file) options.body = data;
    else {options.headers['Content-Type']='application/json';options.body=JSON.stringify(data);}
  }
  const res = await fetch('api.php?action='+encodeURIComponent(action),options);
  const result = await res.json().catch(()=>({error:'Unexpected server response'}));
  if (!res.ok) throw new Error(result.error || 'Request failed');
  return result;
}
function renderSubscriptions() {
  const query = search.value.trim().toLowerCase();
  const matching = categories.filter(c=>c.name.toLowerCase().includes(query));
  list.replaceChildren();
  const total = Object.keys(followed).length;
  document.getElementById('subscription-status').textContent = `${total} ${total===1?'category':'categories'} followed`;
  if (!matching.length) {const p=document.createElement('p');p.className='subscription-empty';p.textContent='No matching news categories found.';list.append(p);return;}
  matching.sort((a,b)=>Number(!!followed[b.id])-Number(!!followed[a.id]));
  for (const c of matching) {
    const active=!!followed[c.id], item=document.createElement('article');item.className='category-item';
    const info=document.createElement('div');info.className='category-info';
    const h=document.createElement('h3');h.textContent=c.name;
    const p=document.createElement('p');p.textContent=c.description;
    const status=document.createElement('span');status.className='category-state '+(active?'is-followed':'');status.textContent=active?'✓ Subscribed':'Not subscribed';
    info.append(h,p,status);const actions=document.createElement('div');actions.className='category-actions';
    if(active){const label=document.createElement('label');label.className='notification-toggle';const toggle=document.createElement('input');toggle.type='checkbox';toggle.checked=followed[c.id].notifications;toggle.setAttribute('aria-label','Notifications for '+c.name);toggle.addEventListener('change',async()=>{try{await api('subscription',{category_id:c.id,subscribe:true,notifications:toggle.checked});followed[c.id].notifications=toggle.checked;feedback.textContent='Notification preference saved.';}catch(e){toggle.checked=!toggle.checked;feedback.textContent=e.message;}});const t=document.createElement('span');t.textContent='Notifications';label.append(toggle,t);actions.append(label);}
    const btn=document.createElement('button');btn.type='button';btn.className='category-button '+(active?'unsubscribe':'');btn.textContent=active?'Unsubscribe':'+ Subscribe';btn.addEventListener('click',async()=>{btn.disabled=true;try{await api('subscription',{category_id:c.id,subscribe:!active,notifications:true});if(active)delete followed[c.id];else followed[c.id]={notifications:true};feedback.textContent=active?'Unsubscribed successfully.':'Subscribed successfully.';renderSubscriptions();}catch(e){feedback.textContent=e.message;btn.disabled=false;}});actions.append(btn);item.append(info,actions);list.append(item);
  }
}
search.addEventListener('input',renderSubscriptions);
// Count-up effect keeps the original final values exactly.
const counters = document.querySelectorAll('[data-count]');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const formatCount = (element, value) => element.dataset.format === 'compact'
  ? (value / 1000).toFixed(1) + 'k' : String(Math.round(value));
if (!reducedMotion) {
  counters.forEach(element => {
    const end = Number(element.dataset.count);
    const start = performance.now();
    const duration = 900;
    const animate = now => {
      const t = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - t, 3);
      element.textContent = formatCount(element, end * eased);
      if (t < 1) requestAnimationFrame(animate);
      else element.textContent = formatCount(element, end);
    };
    requestAnimationFrame(animate);
  });
}

// Account settings are persisted in MySQL through api.php.
const settingsForm=document.getElementById('settingsForm');
const settingsFeedback=document.getElementById('settingsFeedback');
const settingsViews=[...document.querySelectorAll('.settings-view')];
const settingsLinks=[...document.querySelectorAll('.settings-side-link')];
function showSettingsSection(section){settingsViews.forEach(v=>v.hidden=v.dataset.view!==section);settingsLinks.forEach(b=>{const on=b.dataset.section===section;b.classList.toggle('selected',on);b.setAttribute('aria-current',on?'true':'false')});}
settingsLinks.forEach(b=>b.addEventListener('click',()=>showSettingsSection(b.dataset.section)));
document.getElementById('goSubscriptions').addEventListener('click',()=>document.querySelector('[data-tab="subscription"]').click());
const degreeOptions={'Faculty of Computing':['Software Engineering','Computer Science','Information Systems','Data Science'],'Faculty of Business':['Business Management','Accounting and Finance','Marketing Management'],'Faculty of Engineering':['Civil Engineering','Mechanical Engineering','Electrical Engineering'],'Faculty of Science':['Biomedical Science','Applied Sciences'],'Faculty of Postgraduate & Professional Advancement':['Postgraduate Programme']};
const facultySelect=document.getElementById('faculty'),degreeSelect=document.getElementById('degree');
function updateDegrees(selected=''){degreeSelect.replaceChildren(new Option('Select degree programme',''));for(const d of degreeOptions[facultySelect.value]||[])degreeSelect.add(new Option(d,d));if(selected) {if(![...degreeSelect.options].some(o=>o.value===selected))degreeSelect.add(new Option(selected,selected));degreeSelect.value=selected;}}
facultySelect.addEventListener('change',()=>{updateDegrees();markDirty()});
const batchSelect=document.getElementById('batchYear');for(let y=new Date().getFullYear();y>=2010;y--)batchSelect.add(new Option(String(y),String(y)));
const settingsFields=[...settingsForm.querySelectorAll('input:not([type="password"]):not([type="file"]),select')];
function settingsSnapshot(){const r={};settingsFields.forEach(el=>{r[el.name+(el.type==='checkbox'?':'+el.value:'')]=el.type==='checkbox'?el.checked:el.value});return r;}
function applySettings(data){for(const id of ['fullName','studentId','email','faculty','batchYear'])if(data[id]!=null)document.getElementById(id).value=data[id];updateDegrees(data.degree||'');settingsFields.filter(e=>e.type==='checkbox').forEach(e=>{const key=e.name+':'+e.value;if(typeof data[key]==='boolean')e.checked=data[key]});}
let savedSettings={};function markDirty(){document.getElementById('settingsDirty').textContent=JSON.stringify(settingsSnapshot())!==JSON.stringify(savedSettings)?'● Unsaved changes':'No unsaved changes';}
settingsForm.addEventListener('input',markDirty);settingsForm.addEventListener('change',markDirty);
function settingsMessage(msg,error=false){settingsFeedback.textContent=msg;settingsFeedback.classList.toggle('is-error',error);}
async function loadData(){try{const r=await api('bootstrap');window.NEWS_DESK_CSRF=r.csrf;categories=r.categories;followed=r.followed;applySettings(r.settings);savedSettings=settingsSnapshot();renderSubscriptions();const name=r.settings.fullName;document.querySelector('.name-row h1').textContent=name||'Your Profile';if(r.photo){document.querySelector('.avatar').src=r.photo;document.getElementById('photoPreview').src=r.photo;}if(r.settings.faculty)document.querySelector('.faculty').textContent=r.settings.faculty+(r.settings.degree?' • '+r.settings.degree:'');}catch(e){settingsMessage(e.message,true);feedback.textContent=e.message;}}
settingsForm.addEventListener('submit',async e=>{e.preventDefault();const current=document.getElementById('currentPassword').value,next=document.getElementById('newPassword').value,confirm=document.getElementById('confirmPassword').value;if(current||next||confirm){if(!current||!next||!confirm){showSettingsSection('security');return settingsMessage('Complete all three password fields.',true);}if(next.length<8||next!==confirm){showSettingsSection('security');return settingsMessage('Check the new password and confirmation (8+ characters).',true);}}const data={...settingsSnapshot(),currentPassword:current,newPassword:next,confirmPassword:confirm};try{const r=await api('settings',data);applySettings(r.settings);savedSettings=settingsSnapshot();markDirty();['currentPassword','newPassword','confirmPassword'].forEach(id=>document.getElementById(id).value='');document.querySelector('.name-row h1').textContent=r.settings.fullName;settingsMessage('Changes saved successfully.');}catch(err){settingsMessage(err.message,true);}});
document.getElementById('cancelSettings').addEventListener('click',()=>{applySettings(savedSettings);['currentPassword','newPassword','confirmPassword'].forEach(id=>document.getElementById(id).value='');markDirty();settingsMessage('Changes discarded.');});
for(const button of document.querySelectorAll('.password-eye'))button.addEventListener('click',()=>{const field=document.getElementById(button.dataset.target);field.type=field.type==='password'?'text':'password';button.textContent=field.type==='password'?'Show':'Hide';});
const passwordInput=document.getElementById('newPassword');passwordInput.addEventListener('input',()=>{const v=passwordInput.value;const score=[v.length>=8,/[a-z]/.test(v)&&/[A-Z]/.test(v),/\d/.test(v),/[^A-Za-z0-9]/.test(v)].filter(Boolean).length;document.getElementById('strengthFill').style.width=v?(score*25)+'%':'0';document.getElementById('strengthLabel').textContent=!v?'Use 8+ characters, numbers and symbols.':score<=1?'Weak password':score<=2?'Fair password':score===3?'Good password':'Strong password';});
const photoInput=document.getElementById('profilePhoto'),photoPreview=document.getElementById('photoPreview');
photoInput.addEventListener('change',async()=>{const file=photoInput.files[0];if(!file)return;if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>2097152){photoInput.value='';return settingsMessage('Select a JPG, PNG or WebP under 2 MB.',true);}const form=new FormData();form.append('photo',file);try{const r=await api('photo',form,true);photoPreview.src=r.photo;document.querySelector('.avatar').src=r.photo;settingsMessage('Profile photo updated.');}catch(e){settingsMessage(e.message,true);}});
document.getElementById('removePhoto').addEventListener('click',async()=>{try{await api('remove_photo',{});photoPreview.src='assets/profile.png';document.querySelector('.avatar').src='assets/profile.png';photoInput.value='';settingsMessage('Profile photo removed.');}catch(e){settingsMessage(e.message,true);}});
loadData();
