<?php /* NSBM NewsDesk animated logo preloader for pages shown BEFORE login.
   Set $nsbmPreloaderBase (e.g. '../') before including so the logo path resolves. */
$nsbmPreloaderBase=$nsbmPreloaderBase??''; ?>
<style>
#preloader{position:fixed;inset:0;z-index:99999;display:grid;place-items:center;background:radial-gradient(circle at center,#f5fff9 0%,#fff 65%);transition:opacity .7s ease,visibility .7s ease}
#preloader.is-hidden{opacity:0;visibility:hidden;pointer-events:none}
.nsbm-loader{position:relative;width:210px;height:210px;display:grid;place-items:center;isolation:isolate}
.nsbm-loader::before{content:"";position:absolute;inset:12px;border-radius:50%;background:conic-gradient(from 0deg,#1c60a0,#2fbd58,#9bd342,#1c60a0);filter:blur(14px);opacity:.18;animation:nsbmSpin 5s linear infinite}
.nsbm-loader::after{content:"";position:absolute;inset:23px;border-radius:50%;border:2px solid transparent;border-top-color:#1c60a0;border-bottom-color:#43b956;animation:nsbmSpin 3s linear infinite}
.nsbm-pulse{position:absolute;inset:35px;border:2px solid rgba(55,176,94,.38);border-radius:50%;animation:nsbmWave 2.2s ease-out infinite}
.nsbm-pulse:nth-child(2){animation-delay:1.1s;border-color:rgba(30,99,167,.35)}
.nsbm-loader img{position:relative;z-index:2;width:116px;max-height:140px;object-fit:contain;filter:drop-shadow(0 9px 15px rgba(21,84,120,.15));animation:nsbmFloat 2.5s ease-in-out infinite}
@keyframes nsbmSpin{to{transform:rotate(360deg)}}
@keyframes nsbmWave{0%{transform:scale(.65);opacity:.8}100%{transform:scale(1.5);opacity:0}}
@keyframes nsbmFloat{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-7px) scale(1.045)}}
@media(prefers-reduced-motion:reduce){.nsbm-loader::before,.nsbm-loader::after,.nsbm-pulse,.nsbm-loader img{animation:none}#preloader{transition:none}}
</style>
<div id="preloader" role="status" aria-label="Loading NSBM NewsDesk">
  <div class="nsbm-loader">
    <span class="nsbm-pulse"></span><span class="nsbm-pulse"></span>
    <img src="<?=htmlspecialchars($nsbmPreloaderBase.'assets/images/NSBM NewsDesk Logo.png',ENT_QUOTES)?>" alt="NSBM NewsDesk logo" fetchpriority="high">
  </div>
</div>
<script>
(function(){
  const loader=document.getElementById('preloader');
  const started=performance.now();
  let finished=false;
  function hide(){
    if(finished)return;
    finished=true;
    const delay=Math.max(0,1100-(performance.now()-started));
    setTimeout(()=>{loader.classList.add('is-hidden');setTimeout(()=>loader.remove(),750)},delay);
  }
  if(document.readyState==='complete')hide();
  else window.addEventListener('load',hide,{once:true});
  setTimeout(hide,5000);
})();
</script>
