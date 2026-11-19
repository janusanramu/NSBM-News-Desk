/* Admin UI to MySQL; retains the existing dashboard, tables, modals and sidebar. */
(()=>{'use strict';const {api,toast,categoryGroups,articleCategoryGroup,categoryLabel}=window.NSBM;
let persistedArticles=new Set(),persistedAnnouncements=new Set(),syncing=false,pending=false,categories=[];
const articleId=a=>/^db-(\d+)$/.test(String(a.id))?Number(String(a.id).slice(3)):0;
const annId=a=>/^db-(\d+)$/.test(String(a.id))?Number(String(a.id).slice(3)):0;
const normalStatus=s=>({pending_review:'Pending',published:'Published',rejected:'Rejected',archived:'Archived',draft:'Draft'})[s]||s;
const dateOf=x=>String(x||'').slice(0,10);
const articleFromDB=a=>({id:'db-'+a.id,title:a.title,author:a.author||'NSBM NewsDesk',email:a.author_email||'',category:categoryLabel(a),category_id:Number(a.category_id),category_slug:a.category_slug,category_group:articleCategoryGroup(a),date:dateOf(a.created_at),status:normalStatus(a.status),description:a.summary,body:a.body,reason:a.rejection_reason||'',batch:a.author_intake||'',department:[a.author_faculty,a.author_degree].filter(Boolean).join(' • '),avatar:(typeof a.author_photo_url==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(a.author_photo_url))?((document.body.dataset.base||'/')+a.author_photo_url):'',views:Number(a.view_count)||0});
const annFromDB=a=>({id:'db-'+a.id,title:a.title,message:a.message,date:dateOf(a.date),status:normalStatus(a.status)});
function categoryIdForGroup(slug){const group=categoryGroups.find(entry=>entry.slug===slug);const category=group&&(categories.find(entry=>entry.slug===group.slug)||categories.find(entry=>group.slugs.includes(entry.slug)));return category?Number(category.id):0;}
function articleCategoryId(article){const group=categoryGroups.find(entry=>entry.name===article.category);if(!group)return article.category;if(article.category_id&&article.category_group===group.slug)return article.category_id;const id=categoryIdForGroup(group.slug);if(!id)throw Error('The '+group.name+' news category is not configured in the database.');return id;}
async function load(){try{const [a,n,stats]=await Promise.all([api('articles'),api('announcements'),api('stats')]);articles=(a.articles||[]).map(articleFromDB);announcements=(n.announcements||[]).map(annFromDB);persistedArticles=new Set(articles.map(x=>articleId(x)));persistedAnnouncements=new Set(announcements.map(x=>annId(x)));refreshAll();
 const values=stats.stats||{};for(const [id,key] of [['totalArticles','articles'],['pendingCount','pending'],['readershipCount','readership'],['studentCount','students']]){const el=document.getElementById(id);if(el)el.textContent=Number(values[key]||0).toLocaleString();}
 await Promise.all([loadComments(),loadCategories(),loadReports()]);
}catch(e){toast(e.message,false);}}
function payload(){const a=articles.map(x=>({id:articleId(x),client_id:'tmp-'+x.id,title:x.title,category:articleCategoryId(x),description:x.description||'',body:x.body||x.description||'',status:x.status,reason:x.reason||''}));const n=announcements.map(x=>({id:annId(x),client_id:'tmp-'+x.id,title:x.title,message:x.message,status:x.status}));const liveArticles=new Set(a.map(x=>x.id).filter(Boolean)),liveAnns=new Set(n.map(x=>x.id).filter(Boolean));return {articles:a,announcements:n,deleted_articles:[...persistedArticles].filter(x=>!liveArticles.has(x)),deleted_announcements:[...persistedAnnouncements].filter(x=>!liveAnns.has(x))};}
async function sync(){if(syncing){pending=true;return;}syncing=true;try{const request=payload();const response=await api('admin_sync','POST',request);for(const article of articles){const id=response.article_ids?.['tmp-'+article.id];if(id)article.id='db-'+id;}for(const ann of announcements){const id=response.announcement_ids?.['tmp-'+ann.id];if(id)ann.id='db-'+id;}persistedArticles=new Set(articles.map(articleId).filter(Boolean));persistedAnnouncements=new Set(announcements.map(annId).filter(Boolean));toast('Changes saved.');}catch(e){toast('Save failed: '+e.message,false);await load();}finally{syncing=false;if(pending){pending=false;sync();}}}
document.addEventListener('nsbm-admin-changed',()=>{sync();});
async function loadCategories(){try{categories=(await api('categories')).categories||[];renderCategoryCards();}catch(e){toast(e.message,false);}}
function renderCategoryCards(){const grid=document.getElementById('categoryGrid');if(!grid)return;grid.replaceChildren();const icons={'campus-life':'fa-building-columns','academics':'fa-graduation-cap','research':'fa-flask','sports':'fa-trophy','computing':'fa-laptop-code','business':'fa-briefcase','engineering':'fa-gears'};
 categories.forEach(c=>{const card=document.createElement('article');card.className='bento-category bento-campus';card.style.cssText='min-height:195px;padding:20px;display:flex;flex-direction:column;justify-content:space-between';const title=document.createElement('h3');title.textContent=c.name;const meta=document.createElement('p');meta.textContent=(c.description||'News category')+' · '+c.article_count+' published';const actions=document.createElement('div');actions.style.cssText='display:flex;gap:8px;flex-wrap:wrap';
 const edit=document.createElement('button');edit.className='small-button';edit.textContent='Edit';edit.type='button';edit.addEventListener('click',async()=>{const name=prompt('Category name',c.name);if(!name)return;const description=prompt('Description',c.description||'');if(description===null)return;try{await api('categories','POST',{id:Number(c.id),name,description});await loadCategories();toast('Category updated.');}catch(e){toast(e.message,false);}});
 const remove=document.createElement('button');remove.className='small-button';remove.textContent='Remove';remove.type='button';remove.addEventListener('click',async()=>{if(!confirm('Delete '+c.name+'? Categories with articles cannot be deleted.'))return;try{await api('categories','DELETE',{id:Number(c.id)});await loadCategories();toast('Category removed.');}catch(e){toast(e.message,false);}});
 actions.append(edit,remove);const icon=document.createElement('i');icon.className='fa-solid '+(icons[c.slug]||'fa-folder-open');icon.style.fontSize='26px';card.append(icon,title,meta,actions);grid.append(card);});}
window.renderCategories=renderCategoryCards;
const categoryHeading=document.querySelector('#categoriesPage .page-heading');if(categoryHeading){const add=document.createElement('button');add.className='primary-button';add.type='button';add.textContent='+ Add Category';add.addEventListener('click',async()=>{const name=prompt('Category name');if(!name)return;const description=prompt('Short description')||'';try{await api('categories','POST',{name,description});await loadCategories();toast('Category added.');}catch(e){toast(e.message,false);}});categoryHeading.append(add);}
// Replace local sample comments with the database moderation queue.
let comments=[],commentState='pending';
async function loadComments(){try{comments=(await api('comments')).comments||[];renderComments();}catch(e){toast(e.message,false);}}
window.renderComments=function(){
 const counts={pending:0,approved:0,removed:0};comments.forEach(c=>{if(c.status in counts)counts[c.status]++;});
 for(const [id,key] of [['commentPendingCount','pending'],['approvedCount','approved'],['removedCount','removed']]){const el=document.getElementById(id);if(el)el.textContent=String(counts[key]);}
 const list=document.getElementById('commentsList');if(!list)return;list.replaceChildren();
 const visible=comments.filter(c=>c.status===commentState);if(!visible.length){list.textContent='No '+commentState+' comments found.';return;}
 visible.forEach(c=>{
  const card=document.createElement('article');card.className='content-card';card.style.marginBottom='10px';
  const row=document.createElement('div');row.className='comment-author-row';
  const title=document.createElement('strong');title.textContent=c.author+' · '+c.article_title;
  const avatar=document.createElement('span');avatar.className='profile-comment-avatar';avatar.textContent=(String(c.author||'User').trim().split(/\s+/).slice(0,2).map(x=>x[0]||'').join('').toUpperCase());
  if(typeof c.author_photo_url==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(c.author_photo_url)){
   const img=document.createElement('img');img.className='profile-comment-avatar';img.alt='';img.src=(document.body.dataset.base||'/')+c.author_photo_url;img.onerror=()=>img.replaceWith(avatar);row.append(img);
  }else row.append(avatar);
  row.append(title);const p=document.createElement('p');p.textContent=c.body;card.append(row,p);
  for(const [status,label] of [['approved','Approve'],['removed','Remove'],['pending','Return to pending']]){
   if(status===c.status)continue;const btn=document.createElement('button');btn.className='small-button';btn.type='button';btn.textContent=label;
   btn.addEventListener('click',async()=>{try{await api('comment_moderate','POST',{id:Number(c.id),status});await loadComments();toast('Comment updated.');}catch(e){toast(e.message,false);}});
   card.append(btn);
  }
  list.append(card);
 });
};
window.showComments=function(tab,selected){commentState=tab;document.querySelectorAll('.comment-tabs button').forEach(b=>b.classList.toggle('active',b===selected));renderComments();};
const commentLink=document.querySelector('.side-link[data-page="comments"]');commentLink?.addEventListener('click',()=>loadComments());
async function loadReports(){try{const reports=(await api('reports')).reports||[];let wrapper=document.getElementById('liveReports');if(!wrapper){wrapper=document.createElement('section');wrapper.id='liveReports';wrapper.className='content-card';const heading=document.createElement('h2');heading.textContent='Content Reports';wrapper.append(heading);document.getElementById('reportsPage').append(wrapper);}wrapper.querySelectorAll('article,.empty-message').forEach(el=>el.remove());if(!reports.length){const empty=document.createElement('p');empty.className='empty-message';empty.textContent='No reports have been submitted.';wrapper.append(empty);}reports.forEach(r=>{const card=document.createElement('article');card.className='announcement-card';const text=document.createElement('p');text.textContent='#'+r.id+' · '+r.reason+' · '+r.status+' · '+(r.details||'');const select=document.createElement('select');['open','reviewing','resolved','dismissed'].forEach(status=>select.add(new Option(status,status)));select.value=r.status;select.addEventListener('change',async()=>{try{await api('report_moderate','POST',{id:Number(r.id),status:select.value});toast('Report status updated.');}catch(e){toast(e.message,false);}});card.append(text,select);wrapper.append(card);});}catch(e){toast(e.message,false);}}
const reportLink=document.querySelector('.side-link[data-page="reports"]');reportLink?.addEventListener('click',()=>loadReports());

const sf=document.getElementById('adminSettingsForm');
async function loadSettings(){try{const [account,response]=await Promise.all([api('profile'),api('admin_settings')]);const p=account.profile,settings=response.settings||{};document.getElementById('asName').value=p.name;document.getElementById('asEmail').value=p.email;document.getElementById('asEmail').readOnly=true;document.getElementById('asPhone').value=settings.contact_number||'';if(settings.theme){document.getElementById('asTheme').value=settings.theme;document.getElementById('asTheme').dispatchEvent(new Event('change'));}for(const [id,key] of [['asCompact','compact_tables'],['asNotifyEmail','notify_email'],['asNotifyArticles','notify_articles'],['asNotifyComments','notify_comments'],['asNotifyAnnouncements','notify_announcements']])if(settings[key]!=null)document.getElementById(id).checked=!!Number(settings[key]);}catch(ex){toast(ex.message,false);}}
if(sf){
 const info=document.querySelector('[data-settings-pane="security"] .as-info span');if(info)info.textContent='Enter your current password to securely update it.';
 sf.addEventListener('submit',async e=>{e.preventDefault();e.stopImmediatePropagation();const feedback=document.getElementById('asFeedback');try{
  const name=document.getElementById('asName').value.trim(),phone=document.getElementById('asPhone').value.trim(),theme=document.getElementById('asTheme').value;
  const old=document.getElementById('asCurrent').value,next=document.getElementById('asNew').value,confirm=document.getElementById('asConfirm').value;
  if(old||next||confirm){if(!old||next.length<12||next!==confirm)throw Error('Enter the current password and matching new password (12+ characters).');await api('password','POST',{current:old,next});}
  await api('profile','POST',{name,faculty:'Staff'});
  const values={contact_number:phone,theme,compact_tables:document.getElementById('asCompact').checked,notify_email:document.getElementById('asNotifyEmail').checked,notify_articles:document.getElementById('asNotifyArticles').checked,notify_comments:document.getElementById('asNotifyComments').checked,notify_announcements:document.getElementById('asNotifyAnnouncements').checked};await api('admin_settings','POST',values);document.getElementById('asTheme').dispatchEvent(new Event('change'));
  ['asCurrent','asNew','asConfirm'].forEach(id=>document.getElementById(id).value='');feedback.textContent='Profile and preferences saved.';toast('Administrator settings saved.');
 }catch(ex){feedback.textContent=ex.message;toast(ex.message,false);}},true);
 document.getElementById('asReset')?.addEventListener('click',e=>{e.preventDefault();e.stopImmediatePropagation();loadSettings();},true);
 loadSettings();
}
load();
})();
