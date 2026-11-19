document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var PER_PAGE = 4;
    var currentPage = 1;
    var currentFilter = 'all';
    var submissions = [];
    var list = document.getElementById('articleList');
    var filterSelect = document.getElementById('statusFilter');
    var pager = document.getElementById('pager');
    var showingText = document.getElementById('showingText');
    var emptyState = document.getElementById('emptyState');
    var app = window.NSBM;

    if (!list || !filterSelect || !pager || !showingText || !emptyState || !app) {
        return;
    }

    var statuses = {
        draft: ['Draft', 'draft', 'edit_note'],
        pending_review: ['Pending Review', 'pending_review', 'schedule'],
        published: ['Published', 'approved', 'check_circle'],
        rejected: ['Needs Revision', 'rejected', 'error'],
        archived: ['Archived', 'archived', 'inventory_2']
    };

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function normalizeStatus(status) {
        return statuses[status] ? status : 'draft';
    }

    function formatDate(value) {
        if (!value) return 'Date unavailable';
        var date = new Date(String(value).replace(' ', 'T'));
        return isNaN(date.getTime())
            ? 'Date unavailable'
            : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function imageUrl(value) {
        if (typeof value !== 'string') return app.route('submissions/images/nsbm-campus-aerial.jpg');
        if (!value.includes('..') && /^(uploads\/covers\/|news\/assets\/images\/|home\/images\/)[a-zA-Z0-9._/-]+$/.test(value)) {
            return app.route(value);
        }
        return app.route('submissions/images/nsbm-campus-aerial.jpg');
    }

    function createArticle(item) {
        var status = normalizeStatus(String(item.status || '').toLowerCase());
        var details = statuses[status];
        var article = document.createElement('article');
        article.className = 'article' + (status === 'rejected' ? ' is-rejected' : '');
        article.dataset.status = status === 'published' ? 'approved' : status;

        var row = document.createElement('div');
        row.className = 'article-row';

        var image = document.createElement('img');
        image.className = 'thumb';
        image.src = imageUrl(item.cover_image_url);
        image.alt = String(item.image_alt || item.title || 'Article image');
        image.loading = 'lazy';
        image.onerror = function () {
            this.onerror = null;
            this.src = app.route('submissions/images/nsbm-campus-aerial.jpg');
        };

        var content = document.createElement('div');
        content.className = 'article-body';
        var meta = document.createElement('div');
        meta.className = 'meta';
        var badge = document.createElement('span');
        badge.className = 'badge ' + details[1];
        var icon = document.createElement('span');
        icon.className = 'material-symbols-outlined';
        icon.textContent = details[2];
        badge.append(icon, document.createTextNode(details[0]));
        meta.appendChild(badge);

        function addMeta(value) {
            var span = document.createElement('span');
            span.className = 'meta-text';
            span.textContent = '• ' + value;
            meta.appendChild(span);
        }

        addMeta(String(item.category || 'General'));
        addMeta('Submitted ' + formatDate(item.submitted_at || item.created_at));

        var heading = document.createElement('h3');
        heading.textContent = String(item.title || 'Untitled article');
        if (status === 'published') {
            var link = document.createElement('a');
            link.href = app.route('article.php?id=' + encodeURIComponent(item.id));
            link.textContent = heading.textContent;
            heading.replaceChildren(link);
        }

        var summary = document.createElement('p');
        summary.textContent = String(item.summary || '');
        content.append(meta, heading, summary);
        row.append(image, content);
        article.appendChild(row);

        if (status === 'rejected' && item.rejection_reason) {
            var note = document.createElement('div');
            note.className = 'rejection-note';
            var noteTitle = document.createElement('div');
            noteTitle.className = 'rejection-title';
            noteTitle.textContent = 'Reviewer Feedback';
            var feedback = document.createElement('p');
            feedback.textContent = String(item.rejection_reason);
            note.append(noteTitle, feedback);
            article.appendChild(note);
        }

        if (status !== 'archived') {
            var actions = document.createElement('div');
            actions.className = 'submission-controls';
            var edit = document.createElement('a');
            edit.className = 'submission-edit';
            edit.textContent = 'Edit';
            edit.href = app.route('submit-article.php?edit=' + encodeURIComponent(item.id));
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'submission-delete';
            remove.textContent = 'Delete';
            actions.append(edit, remove);
            article.appendChild(actions);

            remove.addEventListener('click', async function () {
                if (!window.confirm('Delete this article submission? This cannot be undone.')) return;
                remove.disabled = true;
                try {
                    await app.api('articles', 'DELETE', { id: Number(item.id) });
                    app.toast('Article deleted.');
                    await loadSubmissions();
                } catch (error) {
                    app.toast(error.message, false);
                    remove.disabled = false;
                }
            });
        }

        return article;
    }

    function allArticles() {
        return Array.prototype.slice.call(list.querySelectorAll('.article'));
    }

    function updateStats() {
        var total = submissions.length;
        var approved = submissions.filter(function (item) { return item.status === 'published'; }).length;
        var pending = submissions.filter(function (item) { return item.status === 'pending_review'; }).length;
        var rejected = submissions.filter(function (item) { return item.status === 'rejected'; }).length;
        document.getElementById('statTotal').textContent = pad(total);
        document.getElementById('statApproved').textContent = pad(approved);
        document.getElementById('statPending').textContent = pad(pending);
        document.getElementById('statRejected').textContent = pad(rejected);
    }

    function renderPager(totalPages) {
        pager.replaceChildren();

        function addButton(label, page, options) {
            var element = document.createElement(options.disabled || options.current ? 'span' : 'a');
            element.className = 'pg' + (options.current ? ' current' : '') + (options.disabled ? ' disabled' : '');
            element.textContent = label;
            if (!options.disabled && !options.current) {
                element.href = '#';
                element.addEventListener('click', function (event) {
                    event.preventDefault();
                    currentPage = page;
                    render();
                });
            }
            pager.appendChild(element);
        }

        addButton('Prev', currentPage - 1, { disabled: currentPage === 1 });
        for (var page = 1; page <= totalPages; page++) {
            addButton(String(page), page, { current: page === currentPage });
        }
        addButton('Next', currentPage + 1, { disabled: currentPage === totalPages });
    }

    function render() {
        var items = allArticles();
        var matches = items.filter(function (article) {
            return currentFilter === 'all' || article.dataset.status === currentFilter;
        });
        var totalPages = Math.max(1, Math.ceil(matches.length / PER_PAGE));
        currentPage = Math.min(currentPage, totalPages);

        var start = (currentPage - 1) * PER_PAGE;
        var end = start + PER_PAGE;
        items.forEach(function (article) { article.style.display = 'none'; });
        matches.slice(start, end).forEach(function (article) { article.style.display = ''; });

        showingText.textContent = 'Showing ' + (matches.length ? start + 1 : 0) + '-' +
            Math.min(end, matches.length) + ' of ' + matches.length + ' submissions';
        emptyState.textContent = matches.length
            ? ''
            : submissions.length
                ? 'No submissions match this status.'
                : 'No submissions yet.';
        emptyState.style.display = matches.length ? 'none' : 'block';
        renderPager(totalPages);
    }

    async function loadSubmissions() {
        emptyState.textContent = 'Loading your submissions...';
        emptyState.style.display = 'block';
        try {
            var response = await app.api('articles');
            submissions = (response.articles || []).map(function (item) {
                return Object.assign({}, item, { status: normalizeStatus(String(item.status || '').toLowerCase()) });
            });
            list.replaceChildren();
            submissions.forEach(function (item) { list.appendChild(createArticle(item)); });
            updateStats();
            render();
        } catch (error) {
            emptyState.textContent = 'Your submissions could not be loaded. ' + error.message;
            emptyState.style.display = 'block';
            showingText.textContent = 'Submission history unavailable';
            app.toast(error.message, false);
        }
    }

    filterSelect.addEventListener('change', function () {
        currentFilter = filterSelect.value;
        currentPage = 1;
        render();
    });

    var tickerText = document.querySelector('.breaking-text');
    var tickerTrack = document.querySelector('.breaking-track');
    if (tickerText && tickerTrack) {
        var distance = tickerText.scrollWidth + tickerTrack.clientWidth;
        tickerText.style.paddingLeft = tickerTrack.clientWidth + 'px';
        tickerText.style.animationDuration = Math.max(20, distance / 60) + 's';
    }

    loadSubmissions();
    if (new URLSearchParams(window.location.search).get('updated') === '1') {
        app.toast('Article updated and sent for review.');
        window.history.replaceState({}, '', window.location.pathname);
    }
});
