/* NSBM NewsDesk: original source artwork/layout, live shared database content.
   Cards are cloned from the team's existing HTML, not redesigned. */
(() => {
  'use strict';
  const { api, route, role, toast, categoryGroups, categoryGroup, matchesCategory, articleCategoryGroup, categoryLabel } = window.NSBM;
  const page = document.body.dataset.pageType;
  if (page !== 'home' && page !== 'news') return;
  const isNews = page === 'news';
  const grid = document.querySelector(isNews ? '.newsdesk-page #news-grid' : '.newsdesk-page .card-grid');
if (!grid) return;

// Hide initial dummy cards until database news is ready
grid.style.visibility = 'hidden';
  const originalCard = grid.querySelector(isNews ? '.news-card' : '.card')?.cloneNode(true);
  const newsRoute = role === 'guest' ? 'news.php' : role === 'admin' ? 'admin-news.php' : 'student-news.php';
  const detail = a => route('article.php?id=' + encodeURIComponent(a.id));
  function renderAnnouncements(items) {
    const panel = document.querySelector('.newsdesk-page .announcements-panel, .newsdesk-page .notices-panel');
    const list = panel?.querySelector('.notice-list');
    if (!panel || !list) return;
    list.replaceChildren();
    if (!items.length) {
      const empty = document.createElement('p');
      empty.className = 'announcement-empty';
      empty.textContent = 'There are no current announcements.';
      list.append(empty);
      return;
    }
        const isStudentPanel = panel.classList.contains('announcements-panel');
    for (const announcement of items) {
      const card = document.createElement('article');
      card.className = isStudentPanel ? 'notice notice-normal' : 'notice-card';
      const title = document.createElement('h4');
      title.textContent = announcement.title;
      const message = document.createElement('p');
      message.className = 'notice-text';
      const full = String(announcement.message || '');
      const isLong = full.length > 140;
      const short = isLong ? full.slice(0, 140).trimEnd() + '…' : full;
      message.textContent = short;
      const more = document.createElement('span');
      more.className = 'notice-more';
      more.textContent = 'Read more ▾';
      more.hidden = !isLong;
      if (isStudentPanel) {
        const top = document.createElement('div');
        top.className = 'notice-top';
        const label = document.createElement('span');
        label.className = 'priority priority-normal';
        label.textContent = 'ANNOUNCEMENT';
        const date = document.createElement('span');
        date.className = 'notice-time';
        date.textContent = fmtDate(announcement.date);
        top.append(label, date);
        card.append(top, title, message, more);
      } else {
        const label = document.createElement('div');
        label.className = 'notice-meta-tag';
        label.textContent = 'ANNOUNCEMENT';
        const date = document.createElement('span');
        date.className = 'notice-date';
        date.textContent = fmtDate(announcement.date);
        card.append(label, title, message, date, more);
      }
      if (isLong) {
        card.classList.add('notice-expandable');
        card.tabIndex = 0;
        card.setAttribute('role', 'button');
        card.setAttribute('aria-expanded', 'false');
        const toggle = () => {
          const open = card.classList.toggle('is-open');
          message.textContent = open ? full : short;
          more.textContent = open ? 'Show less ▴' : 'Read more ▾';
          card.setAttribute('aria-expanded', String(open));
        };
        card.addEventListener('click', toggle);
        card.addEventListener('keydown', e => {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
        });
      }
      list.append(card);
    }    
  }
  const img = a => { const p = String(a.cover_image_url || ''); return /^(news\/assets\/images\/|home\/images\/|uploads\/covers\/)[a-zA-Z0-9%._/-]+$/.test(p) ? route(p) : route('home/images/nsbm-campus-aerial.jpg'); };
  const fmtDate = s => { const d = new Date(String(s || '').replace(' ', 'T')); return Number.isFinite(d.getTime()) ? d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; };
    const timeAgo = s => {
    const d = new Date(String(s || '').replace(' ', 'T'));
    if (!Number.isFinite(d.getTime())) return '';
    const sec = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
    if (sec < 60) return 'Just now';
    const min = Math.floor(sec / 60);
    if (min < 60) return min + (min === 1 ? ' minute ago' : ' minutes ago');
    const hr = Math.floor(min / 60);
    if (hr < 24) return hr + (hr === 1 ? ' hour ago' : ' hours ago');
    const day = Math.floor(hr / 24);
    if (day < 7) return day + (day === 1 ? ' day ago' : ' days ago');
    if (day < 30) { const wk = Math.floor(day / 7); return wk + (wk === 1 ? ' week ago' : ' weeks ago'); }
    if (day < 365) { const mo = Math.floor(day / 30); return mo + (mo === 1 ? ' month ago' : ' months ago'); }
    const yr = Math.floor(day / 365);
    return yr + (yr === 1 ? ' year ago' : ' years ago');
  };
  const params = new URLSearchParams(location.search);
  let articles = [], categories = [], bookmarked = new Set(), subscribedCategories = new Set();
  let subscribedOnly = false;
  const requestedCategory=params.get('category')||'all';
  let filter = categoryGroup(requestedCategory)||requestedCategory;
  let search = (params.get('q') || '').trim().toLowerCase();
  let shown = isNews ? 60 : 4;
  const select = (node, query) => node.querySelector(query);
  function text(node, query, value) { const el = select(node, query); if (el) el.textContent = String(value ?? ''); }
  function link(node, query, url) { const el = select(node, query); if (el) el.href = url; }
  function authorAvatar(article, className) {
    const name=article.author||'NSBM NewsDesk',avatar=document.createElement('span');
    avatar.className=className;avatar.textContent=name.split(/\s+/).slice(0,2).map(part=>part[0]||'').join('').toUpperCase();
    if(typeof article.author_photo_url==='string'&&/^uploads\/(?:profile|avatars)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)$/.test(article.author_photo_url)){
      const photo=document.createElement('img');photo.src=route(article.author_photo_url);photo.alt='';photo.className='feed-author-photo';photo.onerror=()=>photo.remove();avatar.append(photo);
    }
    return avatar;
  }
  function drawCard(a, index) {
    const node = originalCard?.cloneNode(true) || document.createElement('article');
    node.dataset.category = a.category_slug || '';
    const image = select(node, '.card-image img'); if (image) { image.src = img(a); image.alt = a.image_alt || a.title; image.loading = 'lazy'; image.onerror = () => { image.onerror = null; image.src = route('home/images/nsbm-campus-aerial.jpg'); }; }
    const url = detail(a), by = a.author || 'NSBM NewsDesk';
    if (isNews) {
      node.className = 'news-card';
      link(node, '.card-image a.image-link', url); link(node, '.card-content h2 a', url);
      text(node, '.card-content h2 a', a.title);
      text(node, '.card-content .description', a.summary);
      text(node, '.card-content .meta span:first-child', fmtDate(a.published_at || a.created_at));
      text(node, '.card-content .meta span:last-child', timeAgo(a.published_at || a.created_at));
      const cat = select(node, '.card-image .category'); if (cat) { const group=articleCategoryGroup(a); cat.textContent = categoryLabel(a); cat.href = route(newsRoute + '?category=' + encodeURIComponent(group || a.category_slug || '')); cat.className = 'category ' + (group || String(a.category_slug || '').replace(/[^a-z0-9-]/g, '')); }
      const author = select(node, '.card-footer .author'); if (author) { author.replaceChildren(); author.append(authorAvatar(a,'avatar'), document.createTextNode(by)); }
      text(node, '.card-footer .views', '◎ ' + Number(a.view_count || 0).toLocaleString());
    } else {
      node.className = 'card';
      // Keep the original team's card markup, including image, category badge,
      // typography, meta area, footer placement, and responsive CSS selectors.
      const wrap = select(node, '.card-image'); if (wrap && image && !image.closest('a')) { const anchor = document.createElement('a'); anchor.href = url; anchor.className = 'newsdesk-card-image-link'; image.replaceWith(anchor); anchor.append(image); }
      link(node, '.card-body h3 a', url); text(node, '.card-body h3 a', a.title);
      text(node, '.card-body p', a.summary);
      const tag = select(node, '.card-image .tag'); if (tag) { const group=articleCategoryGroup(a); tag.className = 'tag tag-' + (group || String(a.category_slug || 'campus').replace(/[^a-z0-9-]/g, '')); tag.textContent = categoryLabel(a); }
            const meta = select(node, '.card-meta'); if (meta) meta.textContent = `${fmtDate(a.published_at || a.created_at)} • ${timeAgo(a.published_at || a.created_at)}`;
      const author = select(node, '.card-footer .author'); if (author) { author.replaceChildren(authorAvatar(a,'content-author-avatar'),document.createTextNode('By '+by)); }
      text(node, '.card-footer .views', Number(a.view_count || 0).toLocaleString() + ' views');
      link(node, '.card-footer .read-link', url);
    }
    let bookmark = select(node, '.bookmark,.bookmark-btn');
    if (role === 'student') {
      if (!bookmark) { bookmark = document.createElement('button'); bookmark.type = 'button'; bookmark.className = 'bookmark-btn'; bookmark.textContent = '♡'; select(node, '.card-footer')?.append(bookmark); }
      bookmark.dataset.bookmark = String(a.id);
      const saved = bookmarked.has(Number(a.id)); bookmark.classList.toggle('saved', saved); bookmark.classList.toggle('active', saved); bookmark.setAttribute('aria-pressed', String(saved)); bookmark.setAttribute('aria-label', saved ? 'Remove bookmark' : 'Save bookmark');
      const icon = bookmark.querySelector('use');
      if (icon) icon.setAttribute('href', saved ? '#i-bookmark-filled' : '#i-bookmark');
    } else bookmark?.remove();
    node.classList.add('newsdesk-card-enter');
    node.style.setProperty('--newsdesk-stagger-delay', `${Math.min(index * 80, 640)}ms`);
    node.addEventListener('animationend', () => node.classList.remove('newsdesk-card-enter'), { once: true });
    return node;
  }
  function update() {
    const result = articles.filter(a => matchesCategory(a,filter) && (!subscribedOnly || subscribedCategories.has(Number(a.category_id))) && (!search || [a.title, a.summary, a.category, a.author].join(' ').toLowerCase().includes(search)));
    grid.replaceChildren();

// Show the grid after replacing dummy cards with live database news
grid.style.visibility = 'visible';
    if (!result.length) { const empty = document.createElement('p'); empty.className = 'nsbm-empty'; empty.textContent = 'No articles match your search.'; grid.append(empty); }
    else result.slice(0, shown).forEach((a, index) => grid.append(drawCard(a, index)));
    if (isNews) { const shownCount = document.getElementById('shown-count'); if (shownCount) shownCount.textContent = String(Math.min(shown, result.length)); const resultCount = document.querySelector('.newsdesk-page .result-count'); if (resultCount) { let total = resultCount.querySelector('[data-news-total]'); if (!total) { total = document.createElement('strong'); total.dataset.newsTotal = ''; resultCount.append(document.createTextNode(' of '), total, document.createTextNode(' articles')); } total.textContent = String(result.length); } }
    if (!isNews) { const load = document.querySelector('.newsdesk-page .btn-load-more'); if (load) load.hidden = shown >= result.length; const count = document.querySelector('.newsdesk-page .count-meta'); if (count) count.textContent = `${subscribedOnly ? 'Subscribed feed: ' : 'Showing '}${Math.min(shown, result.length)} of ${result.length} published stories`; }
  }
  function installFilters() {
    const el = document.querySelector(isNews ? '.newsdesk-page .filters' : '.newsdesk-page .category-tabs'); if (!el) return; el.replaceChildren();
    for (const c of [{ slug: 'all', name: 'All News' }, ...categoryGroups]) { const btn = document.createElement('button'); btn.type = 'button'; btn.className = (isNews ? 'filter' : 'cat-btn') + (c.slug === filter ? ' active' : ''); btn.dataset.filter = c.slug; btn.textContent = c.name; btn.addEventListener('click', () => { filter = c.slug; shown = isNews ? 60 : 4; el.querySelectorAll('button').forEach(b => b.classList.toggle('active', b === btn)); const url=new URL(location.href);if(filter==='all')url.searchParams.delete('category');else url.searchParams.set('category',filter);history.replaceState(null,'',url);update(); }); el.append(btn); }
  }
  function updateHero(){if(page!=='home'||!['guest','student'].includes(role)||!articles.length)return;const hero=document.querySelector('.newsdesk-page .hero-feature');if(!hero)return;
 const stories=[...articles].sort((a,b)=>Number(b.is_featured)-Number(a.is_featured)||String(b.published_at||b.created_at).localeCompare(String(a.published_at||a.created_at)));
 let current=Math.max(0,stories.findIndex(a=>Number(a.is_featured)===1)),timer=0,transitionTimer=0;
 const image=hero.querySelector('.hero-image-wrap img'),button=hero.querySelector('.hero-actions button'),read=hero.querySelector('.hero-actions a');
 const controls=document.createElement('div');controls.className='hero-carousel-controls';controls.setAttribute('role','group');controls.setAttribute('aria-label','Featured news controls');
 const previous=document.createElement('button');previous.type='button';previous.className='hero-carousel-arrow';previous.setAttribute('aria-label','Previous featured story');previous.textContent='‹';
 const dots=document.createElement('div');dots.className='hero-carousel-dots';
 const next=document.createElement('button');next.type='button';next.className='hero-carousel-arrow';next.setAttribute('aria-label','Next featured story');next.textContent='›';
 const dotButtons=stories.map((story,index)=>{const dot=document.createElement('button');dot.type='button';dot.className='hero-carousel-dot';dot.setAttribute('aria-label','Show featured story '+(index+1)+': '+story.title);dot.addEventListener('click',()=>show(index));dots.append(dot);return dot;});
 controls.append(previous,dots,next);hero.append(controls);
 const repaintBookmark=()=>{if(!button)return;const saved=bookmarked.has(Number(stories[current].id));button.setAttribute('aria-pressed',String(saved));button.classList.toggle('active',saved);button.classList.toggle('saved',saved);const icon=button.querySelector('use');if(icon)icon.setAttribute('href',saved?'#i-bookmark-filled':'#i-bookmark');const label=button.lastChild;if(label?.nodeType===Node.TEXT_NODE)label.textContent=saved?' Bookmarked':' Bookmark Story';};
 const paint=()=>{const a=stories[current];text(hero,'.hero-title',a.title);text(hero,'.hero-excerpt',a.summary);text(hero,'.date-meta',categoryLabel(a)+' • '+fmtDate(a.published_at||a.created_at));link(hero,'.hero-actions a',detail(a));if(read){read.setAttribute('aria-label','Read full story: '+a.title);}if(image){image.src=img(a);image.alt=a.image_alt||a.title;image.onerror=()=>{image.onerror=null;image.src=route('home/images/nsbm-campus-aerial.jpg');};}dotButtons.forEach((dot,index)=>{dot.classList.toggle('active',index===current);dot.setAttribute('aria-current',index===current?'true':'false');});repaintBookmark();};
 const show=(index,animate=true)=>{current=(index+stories.length)%stories.length;clearTimeout(transitionTimer);if(!animate){paint();return;}hero.classList.remove('is-entering','is-leaving');void hero.offsetWidth;hero.classList.add('is-leaving');transitionTimer=setTimeout(()=>{paint();hero.classList.remove('is-leaving');hero.classList.add('is-entering');transitionTimer=setTimeout(()=>hero.classList.remove('is-entering'),500);},280);};
 const stop=()=>{clearInterval(timer);timer=0;};
 const start=()=>{stop();if(stories.length>1&&!document.hidden&&!hero.contains(document.activeElement))timer=setInterval(()=>show(current+1),2500);};
 previous.addEventListener('click',()=>{show(current-1);start();});next.addEventListener('click',()=>{show(current+1);start();});
 hero.addEventListener('focusin',stop);hero.addEventListener('focusout',event=>{if(!hero.contains(event.relatedTarget))start();});document.addEventListener('visibilitychange',start);
 if(button)button.addEventListener('click',async e=>{e.preventDefault();const a=stories[current],saved=!bookmarked.has(Number(a.id));button.disabled=true;try{await api('bookmarks','POST',{article_id:Number(a.id),saved});saved?bookmarked.add(Number(a.id)):bookmarked.delete(Number(a.id));repaintBookmark();update();toast(saved?'Story bookmarked.':'Bookmark removed.');}catch(ex){toast(ex.message,false);}finally{button.disabled=false;}},true);
 paint();start();
}
  function installSubscriptions() {
    const modal = document.querySelector('.newsdesk-page #subscriptionModal'); if (!modal) return; const trigger = document.querySelector('.newsdesk-page #subscribeAlertsBtn,.newsdesk-page #subscribeFeedBtn'); const close = modal.querySelector('#closeSubscriptionModal'), cancel = modal.querySelector('#cancelSubscription'), save = modal.querySelector('#saveSubscription'), options = modal.querySelector('#subscriptionOptions'), feedback = modal.querySelector('#subscriptionFeedback');
    const hide = () => { modal.hidden = true; document.body.classList.remove('subscription-open'); };
    trigger?.addEventListener('click', async e => {
      e.preventDefault(); e.stopImmediatePropagation(); if (role === 'guest') { location.href = route('NSBM-News-Desk-feature-login-register-page/login.php'); return; }
      if (role === 'admin') { location.href = route('admin.php#categories'); return; }
      try {
        const [sub, categoryResult] = await Promise.all([
          api('subscriptions'),
          categories.length ? Promise.resolve({ categories }) : api('categories')
        ]);
        if (!categories.length) categories = categoryResult.categories || [];
        const followed = new Set((sub.subscriptions || []).map(x => Number(x.category_id)));
        options.replaceChildren();
        for (const group of categoryGroups) {
          const label = document.createElement('label');
          label.className = 'subscription-option';
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          const ids=categories.filter(cat=>group.slugs.includes(cat.slug)&&Number(cat.is_active)!==0).map(cat=>Number(cat.id));
          checkbox.value = group.slug;
          checkbox.checked = ids.some(id=>followed.has(id));
          checkbox.name = 'subscriptionCategory';
          label.append(checkbox, document.createTextNode(' ' + group.name));
          options.append(label);
        }
        modal.dataset.followed = JSON.stringify([...followed]);
        feedback.textContent = '';
        modal.hidden = false;
        document.body.classList.add('subscription-open');
        close?.focus();
      } catch (err) { toast(err.message, false); }
    }, true);
    close?.addEventListener('click', hide); cancel?.addEventListener('click', hide); modal.addEventListener('click', e => { if (e.target === modal) hide(); }); document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) hide(); });
    save?.addEventListener('click', async () => {
      const old = new Set(JSON.parse(modal.dataset.followed || '[]'));
      const currentGroups = new Set([...options.querySelectorAll('input:checked')].map(x => x.value));
      const current = new Set(categoryGroups.filter(group=>currentGroups.has(group.slug)).flatMap(group=>categories.filter(cat=>group.slugs.includes(cat.slug)&&Number(cat.is_active)!==0).map(cat=>Number(cat.id))));
      save.disabled = true;
      feedback.textContent = 'Saving…';
      try {
        await Promise.all([...new Set([...old, ...current])].filter(id => old.has(id) !== current.has(id)).map(id => api('subscriptions', 'POST', { category_id: id, subscribed: current.has(id), notifications: true })));
        subscribedCategories = current;
        subscribedOnly = current.size > 0;
        shown = isNews ? 60 : 4;
        update();
        feedback.textContent = 'Subscriptions saved.';
        toast(current.size ? 'Subscriptions saved. Showing news from your selected categories.' : 'Subscriptions cleared. Showing all news.');
        hide();
      } catch (err) { feedback.textContent = err.message; toast(err.message, false); } finally { save.disabled = false; }
    });
  }
  const field = document.querySelector(isNews ? '.newsdesk-page #archive-search' : '.newsdesk-page .filter-row input[type="search"]'); if (field) { field.value = params.get('q') || ''; field.addEventListener('input', () => { search = field.value.trim().toLowerCase(); update(); }); }
  // Keep the header search and the original page search in sync.
  const headerSearch = document.querySelector('.newsdesk-page .top-search');
  if (headerSearch) headerSearch.value = params.get('q') || '';
  headerSearch?.addEventListener('input', e => { search = e.target.value.trim().toLowerCase(); if (field) field.value = e.target.value; update(); });
  document.querySelector('.newsdesk-page .btn-load-more')?.addEventListener('click', () => { shown += 4; update(); });
  grid.addEventListener('click', async e => { const button = e.target.closest('button[data-bookmark]'); if (!button) return; e.preventDefault(); const id = Number(button.dataset.bookmark), next = !bookmarked.has(id); button.disabled = true; try { await api('bookmarks', 'POST', { article_id: id, saved: next }); next ? bookmarked.add(id) : bookmarked.delete(id); update(); toast(next ? 'Article bookmarked.' : 'Bookmark removed.'); } catch (ex) { button.disabled = false; toast(ex.message, false); } });
  const newsletter = document.querySelector('.newsdesk-page .newsletter-form'); newsletter?.addEventListener('submit', async e => { e.preventDefault(); const input = newsletter.querySelector('input[type="email"]'); if (!input?.value) return; try { await api('newsletter', 'POST', { email: input.value.trim() }); newsletter.reset(); toast('Subscribed to news updates.'); } catch (ex) { toast(ex.message, false); } });
  if (isNews) { const pagination = document.querySelector('.newsdesk-page .pagination'); if (pagination) pagination.hidden = true; }
  installSubscriptions();
  async function boot() {
    try {
      const pub = await api('published');
      articles = pub.articles || [];
      const results = await Promise.allSettled([
        api('categories'),
        role === 'student' ? api('bookmarks') : Promise.resolve({ bookmarks: [] }),
        page === 'home' && role === 'student' ? api('subscriptions') : Promise.resolve({ subscriptions: [] }),
        page === 'home' ? api('announcements') : Promise.resolve({ announcements: [] })
      ]);
      if (results[0].status === 'fulfilled') {
        categories = results[0].value.categories || [];
      } else {
        console.error('Categories API failed:', results[0].reason);
      }
      if (results[1].status === 'fulfilled') {
        bookmarked = new Set((results[1].value.bookmarks || []).map(article => Number(article.id)));
      } else {
        console.error('Bookmarks API failed:', results[1].reason);
      }
      if (results[2].status === 'fulfilled') {
        subscribedCategories = new Set((results[2].value.subscriptions || []).map(subscription => Number(subscription.category_id)));
        subscribedOnly = subscribedCategories.size > 0;
      } else {
        console.error('Subscriptions API failed:', results[2].reason);
      }
      if (results[3].status === 'fulfilled') {
        renderAnnouncements(results[3].value.announcements || []);
      } else {
        console.error('Announcements API failed:', results[3].reason);
      }
      if (filter !== 'all' && !categoryGroups.some(category => category.slug === filter) && !categories.some(category => category.slug === filter && Number(category.is_active) !== 0)) filter = 'all';
      installFilters();
      update();
      updateHero();
        } catch (error) {
      console.error('News API failed:', error);
      toast(error.message, false);
    } finally {
      document.body.classList.add('feed-ready');
    }
  }
  setTimeout(() => document.body.classList.add('feed-ready'), 4000);
  boot();
})();
