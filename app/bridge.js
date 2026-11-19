/* Shared account-aware navigation and API client. */
(()=>{
'use strict';
const root=document.body.dataset.base||'/';const role=document.body.dataset.role||'guest';
const route=path=>root+path.replace(/^\//,'');let csrf='';
async function api(action,method='GET',data,query={}){
 const url=new URL(route('api.php'),location.href);url.searchParams.set('action',action);for(const [k,v] of Object.entries(query))url.searchParams.set(k,String(v));
 const isForm=data instanceof FormData;const opts={method,credentials:'same-origin',headers:{'Accept':'application/json'}};
 if(method!=='GET'){if(!csrf){const r=await api('session');csrf=r.csrf;}opts.headers['X-CSRF-Token']=csrf;if(data!==undefined){if(isForm)opts.body=data;else{opts.headers['Content-Type']='application/json';opts.body=JSON.stringify(data);}}}
 let response;try{response=await fetch(url,opts);}catch{throw Error('Network unavailable. Check XAMPP Apache.');}
 let json;try{json=await response.json();}catch{throw Error('Invalid server response. Check the PHP error log.');}
 if(!response.ok)throw Error(json.error||'Request failed.');if(json.csrf)csrf=json.csrf;return json;
}
function toast(message,ok=true){let el=document.getElementById('nsbm-toast');if(!el){el=document.createElement('div');el.id='nsbm-toast';el.setAttribute('role','status');document.body.append(el);}el.className=ok?'nsbm-toast':'nsbm-toast is-error';el.textContent=message;clearTimeout(el._timer);el._timer=setTimeout(()=>el.remove(),4700);}
const categoryGroups=[
 {slug:'campus-life',name:'Campus Life',slugs:['campus-life','events','notices']},
 {slug:'academics',name:'Academics',slugs:['academics','business','computing','engineering']},
 {slug:'research',name:'Research',slugs:['research']},
 {slug:'clubs-societies',name:'Club & Societies',slugs:['clubs-societies']},
 {slug:'sports',name:'Sports',slugs:['sports']}
];
const categoryGroup=slug=>categoryGroups.find(group=>group.slugs.includes(String(slug||'').toLowerCase()))?.slug||'';
const matchesCategory=(article,group)=>group==='all'||categoryGroup(article.category_slug)===group||(group==='research'&&/\bresearch\b/i.test(article.title||''));
const articleCategoryGroup=article=>matchesCategory(article,'research')?'research':categoryGroup(article.category_slug);
const categoryLabel=article=>categoryGroups.find(group=>group.slug===articleCategoryGroup(article))?.name||article.category||'News';
window.NSBM={api,toast,route,role,categoryGroups,categoryGroup,matchesCategory,articleCategoryGroup,categoryLabel};
const header=document.querySelector('body > .site-header');
if(header){
 const toggles=header.querySelectorAll('.dropdown-toggle,.user-toggle');
 toggles.forEach(btn=>{btn.setAttribute('aria-expanded','false');btn.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();let parent=btn.closest('.dropdown');const active=parent.classList.contains('open');header.querySelectorAll('.dropdown.open').forEach(d=>{d.classList.remove('open');d.querySelector('button')?.setAttribute('aria-expanded','false');});parent.classList.toggle('open',!active);btn.setAttribute('aria-expanded',String(!active));});});
 document.addEventListener('click',e=>{if(!e.target.closest('body > .site-header .dropdown'))header.querySelectorAll('.dropdown.open').forEach(d=>{d.classList.remove('open');d.querySelector('button')?.setAttribute('aria-expanded','false');});});
 document.addEventListener('keydown',e=>{if(e.key==='Escape')header.querySelectorAll('.dropdown.open').forEach(d=>{d.classList.remove('open');d.querySelector('button')?.setAttribute('aria-expanded','false');});});
 header.querySelector('#navToggle')?.addEventListener('click',()=>{const menu=header.querySelector('#mainNav');const open=menu.classList.toggle('open');header.querySelector('#navToggle').setAttribute('aria-expanded',String(open));});
 header.querySelector('form.search-box')?.addEventListener('submit',e=>{e.preventDefault();const q=header.querySelector('input[name="q"]')?.value.trim()||'';location.href=route((role==='student'?'student-news.php':role==='admin'?'admin-news.php':'news.php')+'?q='+encodeURIComponent(q));});
}
// Replace links left over from the original standalone pages.
const map={'../news/index.html':role==='guest'?'news.php':role==='admin'?'admin-news.php':'student-news.php','../article.html':role==='guest'?'news.php':'student-news.php','article-details.html':role==='student'?'student-news.php':'news.php','register.php':'NSBM-News-Desk-feature-login-register-page/register.php','login.php':'NSBM-News-Desk-feature-login-register-page/login.php','../login.php':'NSBM-News-Desk-feature-login-register-page/login.php','logout.php':'NSBM-News-Desk-feature-login-register-page/logout.php','index.html':role==='guest'?'index.php':role==='admin'?'admin.php':'student-home.php'};
document.querySelectorAll('.newsdesk-page a[href]').forEach(a=>{const url=a.getAttribute('href');if(url&&map[url])a.href=route(map[url]);});
if(role==='admin'&&location.hash){const key=location.hash.slice(1);const link=document.querySelector('.newsdesk-page .side-link[data-page="'+CSS.escape(key)+'"]');link&&setTimeout(()=>link.click(),0);}
})();
