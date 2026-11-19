(() => {
  'use strict';
  const { api, route } = window.NSBM || {};
  const toggle = document.querySelector('.notification-toggle');
  const menu = document.getElementById('notificationMenu');
  const list = menu?.querySelector('.notification-items');
  const dot = toggle?.querySelector('.notif-dot');
  if (!api || !toggle || !menu || !list || !dot) return;

  function render(items) {
    list.replaceChildren();
    if (!items.length) {
      const empty = document.createElement('p');
      empty.className = 'notification-empty';
      empty.textContent = 'No new news in your subscribed categories.';
      list.append(empty);
      return;
    }
    items.forEach(notification => {
      const link = document.createElement('a');
      link.className = 'notification-item' + (notification.read ? '' : ' is-unread');
      link.href = route('article.php?id=' + encodeURIComponent(notification.id));
      const title = document.createElement('strong');
      title.textContent = notification.title;
      const meta = document.createElement('small');
      meta.textContent = [notification.category, notification.published_at].filter(Boolean).join(' · ');
      link.append(title, meta);
      list.append(link);
    });
  }

  async function refresh() {
    try {
      const result = await api('notifications');
      render(result.notifications || []);
      dot.hidden = Number(result.unread_count) === 0;
      toggle.setAttribute('aria-label', Number(result.unread_count) > 0
        ? 'Notifications, ' + result.unread_count + ' unread'
        : 'Notifications');
    } catch (error) {
      list.textContent = 'Notifications could not be loaded.';
      console.error('NewsDesk notifications failed:', error);
    }
  }

  toggle.addEventListener('click', async () => {
    const opening = menu.hidden;
    menu.hidden = !opening;
    toggle.setAttribute('aria-expanded', String(opening));
    if (opening) {
      await refresh();
      if (!dot.hidden) {
        try {
          await api('notifications', 'POST', {});
          await refresh();
        } catch (error) {
          console.error('NewsDesk could not mark notifications as read:', error);
        }
      }
    }
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.notification-wrap')) {
      menu.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      menu.hidden = true;
      toggle.setAttribute('aria-expanded', 'false');
      toggle.focus();
    }
  });

  refresh();
  window.setInterval(refresh, 60000);
})();
