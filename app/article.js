(()=>{
'use strict';
const view=document.querySelector('[data-article-id]');if(!view)return;
const id=Number(view.dataset.articleId);const {api,toast,role,route}=window.NSBM;
const list=document.getElementById('commentsList');
async function comments(){
 if(!list)return;
 try{
  const {comments}=await api('comments','GET',undefined,{article_id:id});list.replaceChildren();
  if(!comments.length){list.textContent='No published comments yet. Be the first to contribute.';return;}
  for(const comment of comments){
   const card=document.createElement('article');card.className='comment-box';
   const row=document.createElement('div');row.className='comment-author-row';
   const fallback=document.createElement('span');fallback.className='profile-comment-avatar';
   fallback.textContent=(String(comment.author||'User').trim().split(/\s+/).slice(0,2).map(part=>part[0]||'').join('').toUpperCase());
   const photo=typeof comment.author_photo_url==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(comment.author_photo_url);
   if(photo){const img=document.createElement('img');img.className='profile-comment-avatar';img.alt='';img.src=route(comment.author_photo_url);img.onerror=()=>img.replaceWith(fallback);row.append(img);}else row.append(fallback);
   const name=document.createElement('strong');name.textContent=comment.author;row.append(name);
   const body=document.createElement('p');body.textContent=comment.body;card.append(row,body);list.append(card);
  }
 }catch(e){list.textContent='Could not load comments.';}
}
if(role==='student'){
 let saved=false;const button=document.getElementById('articleBookmark');
 api('bookmarks').then(data=>{saved=data.bookmarks.some(bookmark=>Number(bookmark.id)===id);button.textContent=saved?'♥ Saved to Bookmarks':'♡ Save Bookmark';}).catch(()=>{});
 button.addEventListener('click',async()=>{try{await api('bookmarks','POST',{article_id:id,saved:!saved});saved=!saved;button.textContent=saved?'♥ Saved to Bookmarks':'♡ Save Bookmark';toast(saved?'Bookmarked!':'Removed from bookmarks.');}catch(e){toast(e.message,false);}});
 document.getElementById('commentForm')?.addEventListener('submit',async event=>{event.preventDefault();const input=document.getElementById('commentBody');try{await api('comments','POST',{article_id:id,body:input.value});input.value='';document.getElementById('commentFeedback').textContent='Comment submitted for moderation.';}catch(error){toast(error.message,false);}});
 document.getElementById('articleReport')?.addEventListener('click',async()=>{const reason=prompt('Why are you reporting this article?');if(!reason)return;try{await api('report','POST',{article_id:id,reason});toast('Report sent for review.');}catch(error){toast(error.message,false);}});
}
comments();
})();
