/* My Bookmarks collection, backed by the signed-in student's saved articles. */
document.addEventListener('DOMContentLoaded',()=>{
 'use strict';
 const {api,route,toast,categoryGroups,matchesCategory,categoryLabel}=window.NSBM||{};
 if(!api||!route||!categoryGroups||!matchesCategory||!categoryLabel)return;
 let bookmarks=[],activeCategory='all',slideIndex=0;
 const grid=document.getElementById('articleGrid');
 const noResults=document.getElementById('noResults');
 const visibleCount=document.getElementById('visibleCount');
 const filterBar=document.getElementById('categoryFilters');
 const fallbackImage=route('home/images/nsbm-campus-aerial.jpg');
 const initials=name=>String(name||'NSBM NewsDesk').trim().split(/\s+/).slice(0,2).map(x=>x[0]||'').join('').toUpperCase();
 const safePhoto=path=>typeof path==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(path)?route(path):'';
 const dateLabel=value=>{const date=new Date(String(value||'').replace(' ','T'));return Number.isNaN(date.getTime())?'':date.toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'});};
  const timeAgo=value=>{const d=new Date(String(value||'').replace(' ','T'));if(Number.isNaN(d.getTime()))return '';const sec=Math.max(0,Math.floor((Date.now()-d.getTime())/1000));if(sec<60)return 'Just now';const min=Math.floor(sec/60);if(min<60)return min+(min===1?' minute ago':' minutes ago');const hr=Math.floor(min/60);if(hr<24)return hr+(hr===1?' hour ago':' hours ago');const day=Math.floor(hr/24);if(day<7)return day+(day===1?' day ago':' days ago');if(day<30){const wk=Math.floor(day/7);return wk+(wk===1?' week ago':' weeks ago');}if(day<365){const mo=Math.floor(day/30);return mo+(mo===1?' month ago':' months ago');}const yr=Math.floor(day/365);return yr+(yr===1?' year ago':' years ago');};
 function createCard(article,index){
  const card=document.createElement('article');card.className='article-card';card.style.animationDelay=(index*85)+'ms';card.dataset.category=article.category||'';
  const imageWrap=document.createElement('div');imageWrap.className='image-wrap';
  const image=document.createElement('img');image.src=article.cover_image_url?route(article.cover_image_url):fallbackImage;image.alt=article.title;image.loading='lazy';image.onerror=()=>{image.onerror=null;image.src=fallbackImage;};
  const category=document.createElement('span');category.className='tag';category.textContent=categoryLabel(article);
  const remove=document.createElement('button');remove.className='bookmark active';remove.type='button';remove.dataset.id=String(article.id);remove.title='Remove bookmark';remove.setAttribute('aria-label','Remove '+article.title+' from bookmarks');remove.innerHTML='<svg viewBox="0 0 24 24" width="18" height="18" fill="white" stroke="white" stroke-width="2" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>';
  imageWrap.append(image,category,remove);
  const content=document.createElement('div');content.className='content';
   const meta=document.createElement('p');meta.className='meta';meta.textContent=[dateLabel(article.published_at||article.created_at),timeAgo(article.published_at||article.created_at)].filter(Boolean).join(' • ');
  const title=document.createElement('h3');title.textContent=article.title;
  const description=document.createElement('p');description.className='description';description.textContent=article.summary||'Read the latest from NSBM NewsDesk.';
  const bottom=document.createElement('div');bottom.className='bottom';
  const author=document.createElement('span');author.className='author';
  const avatar=document.createElement('span');avatar.className='avatar';avatar.textContent=initials(article.author);
  const photo=safePhoto(article.author_photo_url);if(photo){const img=document.createElement('img');img.src=photo;img.alt='';img.onerror=()=>img.remove();avatar.append(img);}
  author.append(avatar,document.createTextNode(article.author||'NSBM NewsDesk'));
  const read=document.createElement('a');read.className='read';read.href=route('article.php?id='+encodeURIComponent(article.id));read.textContent='Read →';
  bottom.append(author,read);content.append(meta,title,description,bottom);card.append(imageWrap,content);
  remove.addEventListener('click',async()=>{remove.disabled=true;try{await api('bookmarks','POST',{article_id:Number(article.id),saved:false});bookmarks=bookmarks.filter(item=>Number(item.id)!==Number(article.id));render();toast('Bookmark removed.');}catch(error){remove.disabled=false;toast(error.message,false);}});
  return card;
 }
 function visibleBookmarks(){
  const query=(document.getElementById('searchInput')?.value||'').trim().toLowerCase();
  return bookmarks.filter(article=>matchesCategory(article,activeCategory)&&(!query||[article.title,article.summary,article.category,article.author].some(value=>String(value||'').toLowerCase().includes(query))));
 }
 function renderFilters(){
  if(activeCategory!=='all'&&!categoryGroups.some(category=>category.slug===activeCategory))activeCategory='all';
  filterBar.replaceChildren();
  const addFilter=(label,value,total)=>{const button=document.createElement('button');button.className='filter'+(activeCategory===value?' active':'');button.type='button';button.dataset.category=value;button.textContent=label+(total===undefined?'':' ('+total+')');button.addEventListener('click',()=>{activeCategory=value;slideIndex=0;render();});filterBar.append(button);};
  addFilter('All','all',bookmarks.length);categoryGroups.forEach(category=>addFilter(category.name,category.slug,bookmarks.filter(article=>matchesCategory(article,category.slug)).length));
 }
 function render(){
  if(!grid)return;const visible=visibleBookmarks();grid.replaceChildren();visible.forEach((article,index)=>grid.append(createCard(article,index)));
  if(visibleCount)visibleCount.textContent=String(visible.length).padStart(2,'0');
  if(noResults)noResults.classList.toggle('hidden',visible.length>0);grid.classList.toggle('hidden',visible.length===0);
  renderFilters();slideIndex=0;positionCards();
 }
 function positionCards(){
  if(!grid)return;const cards=[...grid.querySelectorAll('.article-card')],width=grid.clientWidth||window.innerWidth;
  const center=(cards.length-1)/2,spacing=window.innerWidth<650?188:Math.min(292,Math.max(210,width*.205));
  cards.forEach((card,index)=>{const relative=index-center-slideIndex,angle=Math.max(-15,Math.min(15,relative*5.1)),drop=Math.pow(relative,2)*20;card.style.setProperty('--x',(relative*spacing)+'px');card.style.setProperty('--y',drop+'px');card.style.setProperty('--angle',angle+'deg');card.style.zIndex=String(100-Math.abs(relative)*5);card.style.pointerEvents=Math.abs(relative)>4?'none':'auto';});
  const prev=document.getElementById('prevSlide'),next=document.getElementById('nextSlide');if(prev)prev.disabled=slideIndex<=-center;if(next)next.disabled=slideIndex>=center;
 }
 function moveSlide(direction){const count=grid.querySelectorAll('.article-card').length,limit=(count-1)/2;slideIndex=Math.max(-limit,Math.min(limit,slideIndex+direction));positionCards();}
 async function initialize(){
  try{const response=await api('bookmarks');bookmarks=response.bookmarks||[];render();}
  catch(error){grid.replaceChildren();if(noResults){noResults.classList.remove('hidden');noResults.querySelector('h3').textContent='Could not load bookmarks';noResults.querySelector('p').textContent=error.message;}toast(error.message,false);}
 }
 document.getElementById('searchInput')?.addEventListener('input',()=>{slideIndex=0;render();});
 document.getElementById('prevSlide')?.addEventListener('click',()=>moveSlide(-1));
 document.getElementById('nextSlide')?.addEventListener('click',()=>moveSlide(1));
 window.addEventListener('resize',positionCards);
 let touchX=0;grid?.addEventListener('touchstart',event=>{touchX=event.touches[0].clientX;},{passive:true});
 grid?.addEventListener('touchend',event=>{const dx=event.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45)moveSlide(dx<0?1:-1);},{passive:true});
 initialize();
});
