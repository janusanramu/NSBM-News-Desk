(() => {
  'use strict';
  const { api, route } = window.NSBM || {};
  const form = document.querySelector('body.newsdesk-integrated > .site-header form.search-box');
  const input = form?.querySelector('input[name="q"]');
  if (!api || !form || !input) return;

  const suggestions = document.createElement('div');
  suggestions.className = 'search-suggestions';
  suggestions.id = 'newsSearchSuggestions';
  suggestions.setAttribute('role', 'listbox');
  suggestions.hidden = true;
  form.append(suggestions);
  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-autocomplete', 'list');
  input.setAttribute('aria-controls', suggestions.id);
  input.setAttribute('aria-expanded', 'false');
  input.autocomplete = 'off';
  let timer = 0;
  let requestId = 0;

  function hide() {
    suggestions.hidden = true;
    input.setAttribute('aria-expanded', 'false');
  }

  input.addEventListener('input', () => {
    window.clearTimeout(timer);
    const query = input.value.trim();
    const currentRequest = ++requestId;
    if (query.length < 2) {
      suggestions.replaceChildren();
      hide();
      return;
    }
    timer = window.setTimeout(async () => {
      try {
        const result = await api('published', 'GET', undefined, { q: query });
        if (currentRequest !== requestId) return;
        const articles = result.articles || [];
        suggestions.replaceChildren();
        if (!articles.length) {
          const empty = document.createElement('p');
          empty.className = 'search-suggestions-empty';
          empty.textContent = 'No matching news found.';
          suggestions.append(empty);
        } else {
          articles.slice(0, 6).forEach(article => {
            const link = document.createElement('a');
            link.className = 'search-suggestion';
            link.setAttribute('role', 'option');
            link.href = route('article.php?id=' + encodeURIComponent(article.id));
            const title = document.createElement('strong');
            title.textContent = article.title;
            const summary = document.createElement('small');
            summary.textContent = article.summary || article.category || '';
            link.append(title, summary);
            suggestions.append(link);
          });
        }
        suggestions.hidden = false;
        input.setAttribute('aria-expanded', 'true');
      } catch (error) {
        hide();
        console.error('NewsDesk search suggestions failed:', error);
      }
    }, 220);
  });

  input.addEventListener('keydown', event => {
    if (event.key === 'Escape') hide();
    if (event.key === 'ArrowDown' && !suggestions.hidden) {
      event.preventDefault();
      suggestions.querySelector('a')?.focus();
    }
  });
  suggestions.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      hide();
      input.focus();
    }
  });
  document.addEventListener('click', event => {
    if (!form.contains(event.target)) hide();
  });
})();
