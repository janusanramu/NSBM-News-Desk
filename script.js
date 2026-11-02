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
    if (target !== 'overview' && target !== 'subscription') return;
    const showingSubscription = target === 'subscription';
    overview.hidden = showingSubscription;
    subscription.hidden = !showingSubscription;
    tabs.forEach(t => {
      const active = t === tab;
      t.classList.toggle('active', active);
      if (active) t.setAttribute('aria-current', 'page');
      else t.removeAttribute('aria-current');
      if (t.dataset.tab === 'subscription') t.setAttribute('aria-expanded', String(showingSubscription));
    });
  });
});

const dashboardConfig = window.dashboardConfig;
const categories = dashboardConfig.categories;
const followed = dashboardConfig.followed;
const list = document.getElementById('subscription-list');
const search = document.getElementById('category-search');
const feedback = document.getElementById('subscription-feedback');
async function saveSubscription(action, category, enabled) {
  const response = await fetch(window.location.href, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      csrf_token: dashboardConfig.csrfToken,
      action,
      category,
      enabled
    })
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.error || 'Unable to save your subscription.');
}

function renderSubscriptions() {
  const query = search.value.trim().toLowerCase();
  const matching = categories.filter(c => c.name.toLowerCase().includes(query));
  list.replaceChildren();
  const total = categories.filter(c => Object.hasOwn(followed, c.id)).length;
  document.getElementById('subscription-status').textContent = `${total} ${total === 1 ? 'category' : 'categories'} followed`;
  if (!matching.length) { const empty = document.createElement('p'); empty.className = 'subscription-empty'; empty.textContent = 'No matching news categories found.'; list.append(empty); return; }
  matching.sort((a,b) => Number(Object.hasOwn(followed,b.id)) - Number(Object.hasOwn(followed,a.id)));
  for (const category of matching) {
    const active = Object.hasOwn(followed, category.id);
    const item = document.createElement('article'); item.className = 'category-item';
    const info = document.createElement('div'); info.className = 'category-info';
    const title = document.createElement('h3'); title.textContent = category.name;
    const description = document.createElement('p'); description.textContent = category.description;
    const status = document.createElement('span'); status.className = `category-state ${active ? 'is-followed' : ''}`; status.textContent = active ? '✓ Subscribed' : 'Not subscribed';
    info.append(title, description, status);
    const actions = document.createElement('div'); actions.className = 'category-actions';
    if (active) {
      const label = document.createElement('label'); label.className = 'notification-toggle';
      const toggle = document.createElement('input'); toggle.type = 'checkbox'; toggle.checked = followed[category.id]?.notifications !== false;
      toggle.setAttribute('aria-label', `Notifications for ${category.name}`);
      toggle.addEventListener('change', async () => {
        const enabled = toggle.checked;
        toggle.disabled = true;
        try {
          await saveSubscription('notifications', category.id, enabled);
          followed[category.id].notifications = enabled;
          feedback.textContent = `${category.name} notifications ${enabled ? 'enabled' : 'disabled'}.`;
        } catch (error) {
          toggle.checked = !enabled;
          feedback.textContent = error.message;
        } finally {
          toggle.disabled = false;
        }
      });
      const caption = document.createElement('span'); caption.textContent = 'Notifications'; label.append(toggle, caption); actions.append(label);
    }
    const button = document.createElement('button'); button.type = 'button'; button.className = `category-button ${active ? 'unsubscribe' : ''}`; button.textContent = active ? 'Unsubscribe' : '+ Subscribe';
    button.addEventListener('click', async () => {
      button.disabled = true;
      try {
        await saveSubscription(active ? 'unsubscribe' : 'subscribe', category.id);
        if (active) delete followed[category.id];
        else followed[category.id] = { notifications: true };
        feedback.textContent = `${active ? 'Unsubscribed from' : 'Subscribed to'} ${category.name}.`;
        renderSubscriptions();
      } catch (error) {
        feedback.textContent = error.message;
        button.disabled = false;
      }
    });
    actions.append(button); item.append(info, actions); list.append(item);
  }
}
search.addEventListener('input', renderSubscriptions);
renderSubscriptions();

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
