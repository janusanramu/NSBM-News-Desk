/* Live account data, article submissions and moderation. Native page UI is retained. */
(()=>{
'use strict';
const root=document.body.dataset.base||'/';const role=document.body.dataset.role;
const url=action=>root+'api.php?action='+encodeURIComponent(action);
let csrf='';
async function call(action,method='GET',data){const r=await fetch(url(action),{method,headers:{'Accept':'application/json',...(method!=='GET'?{'Content-Type':'application/json','X-CSRF-Token':csrf}:{})},credentials:'same-origin',...(data===undefined?{}:{body:JSON.stringify(data)})});const result=await r.json();if(!r.ok)throw Error(result.error||'Request failed');return result;}
function notice(message){let box=document.getElementById('newsdesk-alert');if(!box){box=document.createElement('div');box.id='newsdesk-alert';box.setAttribute('role','status');Object.assign(box.style,{position:'fixed',bottom:'20px',right:'20px',zIndex:'99999',background:'#073f30',color:'#fff',padding:'14px 20px',borderRadius:'12px',boxShadow:'0 6px 25px #0003',maxWidth:'340px'});document.body.append(box);}box.textContent=message;clearTimeout(box._timer);box._timer=setTimeout(()=>box.remove(),5500);}
const mapArticle=a=>({id:String(a.id),title:a.title,body:a.body,summary:a.summary,category:a.category,date:(a.created_at||'').slice(0,10),status:({pending_review:'Pending',published:'Published',rejected:'Rejected',draft:'Draft',archived:'Archived'})[a.status]||a.status});
async function liveNews(){
 const data=await call('published');if(!data.articles.length)return;
 const target=document.querySelector('.newsdesk-page');if(!target)return;
 const list=document.createElement('section');list.className='live-publications';list.style.cssText='max-width:1152px;margin:28px auto;padding:24px;border:1px solid #e1e9e4;border-radius:16px;background:#fff;color:#193f32';
 const title=document.createElement('h2');title.textContent='Latest Published Campus Articles';title.style.marginTop='0';list.append(title);
 for(const a of data.articles){const entry=document.createElement('article');entry.style.cssText='padding:15px 0;border-top:1px solid #e1e9e4';const h=document.createElement('h3');h.textContent=a.title;const p=document.createElement('p');p.textContent=a.summary||'';const meta=document.createElement('small');meta.textContent=[a.category,a.author].filter(Boolean).join(' · ');entry.append(h,p,meta);if(role==='student'){
 const save=document.createElement('button');save.type='button';save.textContent='Bookmark';save.style.cssText='margin-left:12px;padding:7px 12px;border-radius:8px;border:1px solid #005a42;background:#fff;color:#005a42;cursor:pointer';save.addEventListener('click',async()=>{try{await call('bookmarks','POST',{article_id:a.id,saved:true});save.textContent='Saved ✓';notice('Article saved to your bookmarks.');}catch(e){notice(e.message);}});entry.append(save);
 }list.append(entry);}target.prepend(list);
}
async function loadStudent(){
 const session=await call('session');csrf=session.csrf;
 const result=await call('articles');
 localStorage.setItem('newsdesk_articles',JSON.stringify(result.articles.map(mapArticle)));
 if(typeof renderContent==='function')renderContent();
 const saved=await call('bookmarks');let local=[];try{local=JSON.parse(localStorage.getItem('newsdesk_bookmarks')||'[]')}catch{}const staticRows=Array.isArray(local)?local.filter(row=>String(row.id).startsWith('news-')):[];localStorage.setItem('newsdesk_bookmarks',JSON.stringify([...saved.bookmarks,...staticRows]));if(typeof renderContent==='function')renderContent();
 try{const {profile}=await call('profile');const name=document.getElementById('fullName');if(name&&!localStorage.getItem('newsdesk_settings'))name.value=profile.name;}catch{}
}
function wireStudent(){
 const form=document.getElementById('articleForm');if(!form)return;
 form.addEventListener('submit',async e=>{
 e.preventDefault();e.stopImmediatePropagation();
 const info=document.getElementById('articleFeedback');const title=document.getElementById('articleTitle').value.trim();const body=document.getElementById('articleBody').value.trim();const category=document.getElementById('articleCategory').value;const summary=document.getElementById('articleSummary').value.trim();
 if(!title||!body){info.textContent='Enter the article title and content.';return;}
 const submit=form.querySelector('[type="submit"]');submit.disabled=true;try{await call('articles','POST',{title,body,category,summary});notice('Article submitted for review.');form.reset();await loadStudent();if(typeof activateTab==='function')activateTab('submissions');history.replaceState(null,'','#submissions');}catch(ex){info.textContent=ex.message;}finally{submit.disabled=false;}
 },true);
 // Intercept deletion of authenticated submissions before the legacy local-only handler.
 document.getElementById('submissionList')?.addEventListener('click',async e=>{
 const b=e.target.closest('button');if(!b||b.textContent.trim()!=='Delete')return;
 const title=b.closest('.content-entry')?.querySelector('h3')?.textContent;const saved=JSON.parse(localStorage.getItem('newsdesk_articles')||'[]');const item=saved.find(a=>a.title===title);if(!item)return;
 e.preventDefault();e.stopImmediatePropagation();if(!confirm('Delete this submission?'))return;
 try{await call('articles','DELETE',{id:item.id});await loadStudent();notice('Article removed.');}catch(err){notice(err.message);}
 },true);
 // Browser UI may save profile preferences; also persist validated name/faculty to MySQL.
 document.getElementById('settingsForm')?.addEventListener('submit',async()=>{
 try{await call('profile','POST',{name:document.getElementById('fullName').value,faculty:document.getElementById('faculty')?.value||''});}catch(err){notice(err.message);}
 });
}
async function loadAdmin(){
 const session=await call('session');csrf=session.csrf;
 const {articles:db}=await call('articles');
 if(typeof articles==='undefined')return;
 const keep=articles.filter(a=>!String(a.id).startsWith('db-'));
 const fromDb=db.map(a=>({id:'db-'+a.id,title:a.title,author:a.author||'Student',email:a.email||'',category:a.category,date:(a.created_at||'').slice(0,10),status:({pending_review:'Pending',published:'Published',rejected:'Rejected',draft:'Draft',archived:'Archived'})[a.status]||a.status,description:a.summary,body:a.body,reason:a.rejection_reason||'',batch:'',department:''}));
 articles=[...fromDb,...keep];if(typeof refreshAll==='function')refreshAll();
}
function wireAdmin(){
 // Existing admin calls saveData() on moderation. Sync database items after that action.
 if(typeof saveData==='function'){
 const original=saveData;
 saveData=function(){original();const dbItems=articles.filter(a=>String(a.id).startsWith('db-'));dbItems.forEach(async a=>{try{await call('moderate','POST',{id:Number(String(a.id).slice(3)),status:a.status,reason:a.reason||''});}catch(err){notice(err.message);}});};
 }
}
if(role==='student'){
 wireStudent();loadStudent().catch(err=>notice(err.message));
}else if(role==='admin'){
 call('session').then(s=>{csrf=s.csrf;wireAdmin();return loadAdmin();}).catch(err=>notice(err.message));
}
if(role==='student'||role==='guest'||role==='admin')liveNews().catch(()=>{});
})();
