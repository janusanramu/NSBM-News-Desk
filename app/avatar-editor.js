/* Shared profile photo editor and authenticated uploader for student/admin profiles. */
(()=>{
'use strict';
const {api,route,role,toast}=window.NSBM||{};
if(!api||!route)return;
const $=id=>document.getElementById(id);
const MAX_FILE=5*1024*1024;
const allowedTypes=new Set(['image/jpeg','image/png','image/webp']);
let profile=null,image=null,zoom=1,rotation=0,offsetX=0,offsetY=0,drag=null,modal=null,stage=null,canvas=null,preview=null,message=null,progress=null,saveButton=null;
const initials=name=>(String(name||'').trim().split(/\s+/).slice(0,2).map(s=>s[0]||'').join('').toUpperCase()||'U');
const initialsNode=(parent,name)=>{let badge=parent.querySelector(':scope > .avatar-initials');if(!badge){badge=document.createElement('span');badge.className='avatar-initials';badge.setAttribute('aria-hidden','true');parent.append(badge);}badge.textContent=initials(name);return badge;};
function safePhotoUrl(path){return typeof path==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(path)?route(path):'';}
function paintImage(el,url,fallback){if(!el)return;if(url){el.src=url;el.hidden=false;el.style.display='';el.onerror=()=>{el.hidden=true;el.removeAttribute('src');if(fallback)fallback.hidden=false;};}else{el.hidden=true;el.removeAttribute('src');if(fallback)fallback.hidden=false;}}
function paintProfile(data){
 const name=data?.name||'NewsDesk User',url=safePhotoUrl(data?.profile_photo_url);
 const header=document.querySelector('.site-header .user-toggle .avatar');
 if(header){const old=header.querySelector('img');if(url){const img=old||document.createElement('img');img.alt='';img.src=url;img.onerror=()=>img.remove();if(!old)header.prepend(img);}else old?.remove();}
 const studentBanner=document.querySelector('.profile-banner .avatar-wrap');
 if(studentBanner){const img=studentBanner.querySelector('img.avatar'),badge=initialsNode(studentBanner,name);badge.hidden=!!url;if(img)paintImage(img,url,badge);}
 const studentPreview=$('photoPreview');
 if(studentPreview){const frame=studentPreview.closest('.avatar-photo-frame'),badge=frame?initialsNode(frame,name):null;if(badge)badge.hidden=!!url;paintImage(studentPreview,url,badge);}
 const adminAvatar=$('asAvatar');if(adminAvatar){const badge=$('asInitials');if(badge){badge.textContent=initials(name);badge.hidden=!!url;}paintImage($('asPhotoPreview'),url,badge);}
 document.querySelectorAll('.author-avatar[data-avatar-url]').forEach(el=>{const src=safePhotoUrl(el.dataset.avatarUrl);if(src){const fallbackText=el.textContent.trim();const img=document.createElement('img');img.className='profile-comment-avatar';img.alt='';img.src=src;img.onerror=()=>{el.textContent=fallbackText;};el.replaceChildren(img);} });
 document.querySelectorAll('#photoUploadButton,#asUploadPhoto').forEach(el=>{el.textContent=url?'Change Photo':'Upload Photo';});
 const remove=$('removePhoto')||$('asRemovePhoto');if(remove)remove.hidden=!url;
}
function setSettingsMessage(text,error=false){const el=$('settingsFeedback')||$('asFeedback');if(el){el.textContent=text;el.classList.toggle(el.id==='asFeedback'?'error':'is-error',error);}}
function ensureModal(){
 if(modal)return;
 modal=document.createElement('div');modal.className='profile-photo-editor';modal.hidden=true;modal.setAttribute('aria-hidden','true');
 modal.innerHTML='<section class="photo-editor-dialog" role="dialog" aria-modal="true" aria-labelledby="photoEditorTitle"><header class="photo-editor-head"><div><h2 id="photoEditorTitle">Adjust profile photo</h2><p>Drag to reposition. Your circular preview updates as you edit.</p></div><button class="photo-editor-close" type="button" aria-label="Close editor">&times;</button></header><div class="photo-editor-workspace"><div class="photo-editor-stage" aria-label="Square crop area; drag image to reposition"><canvas width="360" height="360"></canvas><span class="photo-editor-guide" aria-hidden="true"></span></div><div class="photo-editor-preview-wrap"><canvas class="photo-editor-preview" width="180" height="180" aria-label="Circular profile photo preview"></canvas><span>Profile preview</span></div></div><div class="photo-editor-controls"><div class="photo-editor-control zoom-control"><label for="photoZoom">Zoom <output>100%</output></label><input id="photoZoom" type="range" min="1" max="3" step="0.01" value="1"></div><div class="photo-editor-control"><label>Position <span>Drag image</span></label><button class="photo-editor-button" type="button" data-rotate><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Rotate 90&deg;</button></div><div class="photo-editor-control"><label>Adjustments</label><button class="photo-editor-button" type="button" data-reset>Reset adjustments</button></div></div><div class="photo-editor-progress" role="progressbar" aria-label="Photo upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div><p class="photo-editor-message" role="status" aria-live="polite"></p><footer class="photo-editor-actions"><button class="photo-editor-button" type="button" data-cancel>Cancel</button><div><button class="photo-editor-button" type="button" data-reset>Reset</button><button class="photo-editor-button primary" type="button" data-save>Save Photo</button></div></footer></section>';
 document.body.append(modal);stage=modal.querySelector('.photo-editor-stage');canvas=stage.querySelector('canvas');preview=modal.querySelector('.photo-editor-preview');message=modal.querySelector('.photo-editor-message');progress=modal.querySelector('.photo-editor-progress span');saveButton=modal.querySelector('[data-save]');
 modal.addEventListener('click',e=>{if(e.target===modal)closeEditor();});
 modal.querySelector('.photo-editor-close').addEventListener('click',closeEditor);modal.querySelector('[data-cancel]').addEventListener('click',closeEditor);
 modal.querySelectorAll('[data-reset]').forEach(b=>b.addEventListener('click',reset));
 modal.querySelector('[data-rotate]').addEventListener('click',()=>{rotation=(rotation+90)%360;offsetX=0;offsetY=0;draw();});
 modal.querySelector('#photoZoom').addEventListener('input',e=>{zoom=Number(e.target.value);modal.querySelector('output').value=Math.round(zoom*100)+'%';draw();});
 stage.addEventListener('pointerdown',e=>{drag={x:e.clientX,y:e.clientY,ox:offsetX,oy:offsetY};stage.classList.add('is-dragging');stage.setPointerCapture(e.pointerId);});
 stage.addEventListener('pointermove',e=>{if(!drag)return;const rect=stage.getBoundingClientRect();offsetX=drag.ox+(e.clientX-drag.x)*canvas.width/rect.width;offsetY=drag.oy+(e.clientY-drag.y)*canvas.height/rect.height;draw();});
 const endDrag=()=>{drag=null;stage.classList.remove('is-dragging');};stage.addEventListener('pointerup',endDrag);stage.addEventListener('pointercancel',endDrag);
 modal.querySelector('[data-save]').addEventListener('click',savePhoto);
 document.addEventListener('keydown',e=>{if(!modal||modal.hidden)return;if(e.key==='Escape'){closeEditor();return;}if(e.key==='Tab'){const controls=[...modal.querySelectorAll('button:not(:disabled),input:not(:disabled)')];const first=controls[0],last=controls[controls.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
}
function drawTo(ctx,size,background=''){
 if(!image)return;const rotated=rotation%180!==0,w=rotated?image.height:image.width,h=rotated?image.width:image.height;
 const scale=Math.max(size/w,size/h)*zoom,drawW=image.width*scale,drawH=image.height*scale;
 const factor=size/canvas.width,boundW=(w*scale-size)/2,boundH=(h*scale-size)/2;
 const x=Math.max(-boundW,Math.min(boundW,offsetX*factor)),y=Math.max(-boundH,Math.min(boundH,offsetY*factor));offsetX=x/factor;offsetY=y/factor;
 ctx.clearRect(0,0,size,size);if(background){ctx.fillStyle=background;ctx.fillRect(0,0,size,size);}ctx.save();ctx.translate(size/2+x,size/2+y);ctx.rotate(rotation*Math.PI/180);ctx.drawImage(image,-drawW/2,-drawH/2,drawW,drawH);ctx.restore();
}
function draw(){if(!image||!canvas)return;drawTo(canvas.getContext('2d'),canvas.width);const ctx=preview.getContext('2d');ctx.clearRect(0,0,preview.width,preview.height);ctx.save();ctx.beginPath();ctx.arc(preview.width/2,preview.height/2,preview.width/2,0,Math.PI*2);ctx.clip();drawTo(ctx,preview.width);ctx.restore();}
function reset(){zoom=1;rotation=0;offsetX=0;offsetY=0;modal.querySelector('#photoZoom').value='1';modal.querySelector('output').value='100%';message.textContent='';message.classList.remove('is-error');draw();}
function openEditor(file){ensureModal();const reader=new FileReader();reader.onerror=()=>setSettingsMessage('Could not read the selected image.',true);reader.onload=()=>{const next=new Image();next.onload=()=>{if(next.naturalWidth*next.naturalHeight>50000000){setSettingsMessage('Choose an image with dimensions under 50 megapixels.',true);return;}image=next;reset();modal.hidden=false;document.body.classList.add('photo-editor-open');modal.setAttribute('aria-hidden','false');requestAnimationFrame(()=>modal.classList.add('is-open'));modal.querySelector('.photo-editor-close').focus();};next.onerror=()=>setSettingsMessage('The selected file is not a readable image.',true);next.src=String(reader.result);};reader.readAsDataURL(file);}
function closeEditor(){if(!modal||modal.hidden||saveButton?.disabled)return;modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('photo-editor-open');setTimeout(()=>{if(modal){modal.hidden=true;progress.style.width='0';progress.parentElement.setAttribute('aria-valuenow','0');message.textContent='';}},200);image=null;}
function upload(blob){
 return api('session').then(session=>new Promise((resolve,reject)=>{
  const form=new FormData();form.append('image',blob,'profile-photo.jpg');const xhr=new XMLHttpRequest();xhr.open('POST',route('api.php?action=avatar'));xhr.withCredentials=true;xhr.setRequestHeader('Accept','application/json');xhr.setRequestHeader('X-CSRF-Token',session.csrf);
  xhr.upload.onprogress=e=>{if(e.lengthComputable){const percent=Math.round(e.loaded/e.total*100);progress.style.width=percent+'%';progress.parentElement.setAttribute('aria-valuenow',String(percent));message.textContent='Uploading photo… '+percent+'%';}};
  xhr.onerror=()=>reject(new Error('Network unavailable while uploading the photo.'));
  xhr.onload=()=>{let data;try{data=JSON.parse(xhr.responseText);}catch{reject(new Error('Invalid server response. Check the PHP error log.'));return;}if(xhr.status<200||xhr.status>=300){reject(new Error(data.error||'Photo upload failed.'));return;}resolve(data);};
  xhr.send(form);
 }));
}
async function savePhoto(){
 if(!image)return;modal.querySelectorAll('button,input').forEach(control=>control.disabled=true);saveButton.textContent='Saving…';message.textContent='Preparing adjusted image…';message.classList.remove('is-error');
 const output=document.createElement('canvas');output.width=512;output.height=512;drawTo(output.getContext('2d'),512,'#ffffff');
 output.toBlob(async blob=>{if(!blob){message.textContent='Could not prepare this image.';message.classList.add('is-error');modal.querySelectorAll('button,input').forEach(control=>control.disabled=false);saveButton.textContent='Save Photo';return;}
  try{const result=await upload(blob);profile={...profile,profile_photo_url:result.url};paintProfile(profile);message.textContent='Profile photo saved successfully.';toast('Profile photo saved.');setSettingsMessage('Profile photo saved successfully.');setTimeout(closeEditor,650);}
  catch(error){message.textContent=error.message;message.classList.add('is-error');setSettingsMessage(error.message,true);toast(error.message,false);}
  finally{modal.querySelectorAll('button,input').forEach(control=>control.disabled=false);saveButton.textContent='Save Photo';}
 },'image/jpeg',.9);
}
function bindInput(input){
 if(!input||input.dataset.avatarEditorBound)return;input.dataset.avatarEditorBound='true';input.accept='image/jpeg,image/png,image/webp';
 input.addEventListener('change',e=>{e.stopImmediatePropagation();const file=input.files?.[0];input.value='';if(!file)return;
  const extension=/\.([a-z0-9]+)$/i.exec(file.name)?.[1]?.toLowerCase();if(file.size>MAX_FILE){setSettingsMessage('Choose an image no larger than 5 MB.',true);toast('Choose an image no larger than 5 MB.',false);return;}
  if(!allowedTypes.has(file.type)||!['jpg','jpeg','png','webp'].includes(extension||'')){setSettingsMessage('Choose a JPG, JPEG, PNG or WebP image.',true);toast('Choose a JPG, JPEG, PNG or WebP image.',false);return;}
  setSettingsMessage('Adjust the crop before saving.');openEditor(file);
 },true);
}
function attachCamera(container,input){
 if(!container||!input)return;container.classList.add('avatar-camera-target');if(container.querySelector('.avatar-camera-button'))return;
 const button=document.createElement('button');button.type='button';button.className='avatar-camera-button';button.setAttribute('aria-label','Change profile photo');button.title='Change profile photo';button.innerHTML='<i class="fa-solid fa-camera" aria-hidden="true"></i>';button.addEventListener('click',()=>input.click());container.append(button);
}
function setup(){
 const studentInput=$('profilePhoto'),studentPreview=$('photoPreview'),adminInput=$('asPhoto'),adminAvatar=$('asAvatar');
 const name=$('fullName')?.value||document.querySelector('.name-row h1')?.textContent||'User';
 if(studentInput){bindInput(studentInput);$('photoUploadButton')?.addEventListener('click',()=>studentInput.click());if(studentPreview){const frame=document.createElement('div');frame.className='avatar-photo-frame';studentPreview.before(frame);frame.append(studentPreview);initialsNode(frame,name);studentPreview.hidden=true;attachCamera(frame,studentInput);}const banner=document.querySelector('.profile-banner .avatar-wrap');if(banner){const img=banner.querySelector('img.avatar');if(img)img.hidden=true;initialsNode(banner,name);}}
 if(adminInput){bindInput(adminInput);$('asUploadPhoto')?.addEventListener('click',()=>adminInput.click());attachCamera(adminAvatar,adminInput);}
 const remove=$('removePhoto')||$('asRemovePhoto');remove?.addEventListener('click',async e=>{e.preventDefault();e.stopImmediatePropagation();if(!profile?.profile_photo_url)return;remove.disabled=true;try{await api('avatar','DELETE',{});profile={...profile,profile_photo_url:null};paintProfile(profile);setSettingsMessage('Profile photo removed.');toast('Profile photo removed.');}catch(error){setSettingsMessage(error.message,true);toast(error.message,false);}finally{remove.disabled=false;}},true);
}
async function initialize(){if(role==='guest'){paintProfile({name:'NewsDesk User',profile_photo_url:null});return;}setup();try{const result=await api('profile');profile=result.profile;if(profile)paintProfile(profile);}catch(error){toast(error.message,false);}}
initialize();
})();
