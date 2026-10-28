document.addEventListener('DOMContentLoaded', function () {

    var PER_PAGE = 4;
    var currentPage = 1;
    var currentFilter = 'all';

    var list = document.getElementById('articleList');
    var filterSelect = document.getElementById('statusFilter');
    var pager = document.getElementById('pager');
    var showingText = document.getElementById('showingText');
    var emptyState = document.getElementById('emptyState');

    function allArticles() {
        return Array.prototype.slice.call(list.querySelectorAll('.article'));
    }

    function pad(n) { return n < 10 ? '0' + n : String(n); }

    // ---------- Stats ----------
    function updateStats() {
        var items = allArticles();
        var count = function (s) {
            return items.filter(function (a) { return a.dataset.status === s; }).length;
        };
        document.getElementById('statTotal').textContent = pad(items.length);
        document.getElementById('statApproved').textContent = pad(count('approved'));
        document.getElementById('statPending').textContent = pad(count('pending'));
        document.getElementById('statRejected').textContent = pad(count('rejected'));
    }

    // ---------- Filter + pagination ----------
    function render() {
        var items = allArticles();
        var matches = items.filter(function (a) {
            return currentFilter === 'all' || a.dataset.status === currentFilter;
        });

        var totalPages = Math.max(1, Math.ceil(matches.length / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        var start = (currentPage - 1) * PER_PAGE;
        var end = start + PER_PAGE;

        items.forEach(function (a) { a.style.display = 'none'; });
        matches.slice(start, end).forEach(function (a) { a.style.display = ''; });

        var from = matches.length ? start + 1 : 0;
        var to = Math.min(end, matches.length);
        showingText.textContent = 'Showing ' + from + '-' + to + ' of ' + matches.length + ' submissions';
        emptyState.style.display = matches.length ? 'none' : 'block';

        renderPager(totalPages);
    }

    function renderPager(totalPages) {
        pager.innerHTML = '';

        function addBtn(label, page, opts) {
            opts = opts || {};
            var el = document.createElement(opts.disabled || opts.current ? 'span' : 'a');
            el.className = 'pg' + (opts.current ? ' current' : '') + (opts.disabled ? ' disabled' : '');
            el.textContent = label;
            if (!opts.disabled && !opts.current) {
                el.href = '#';
                el.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    currentPage = page;
                    render();
                });
            }
            pager.appendChild(el);
        }

        addBtn('Prev', currentPage - 1, { disabled: currentPage === 1 });
        for (var i = 1; i <= totalPages; i++) {
            addBtn(String(i), i, { current: i === currentPage });
        }
        addBtn('Next', currentPage + 1, { disabled: currentPage === totalPages });
    }

    filterSelect.addEventListener('change', function () {
        currentFilter = filterSelect.value;
        currentPage = 1;
        render();
    });

    // ---------- Three-dot menus ----------
    function closeMenus(except) {
        document.querySelectorAll('.menu.open').forEach(function (m) {
            if (m !== except) m.classList.remove('open');
        });
    }

    list.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.menu-btn');
        if (btn) {
            ev.stopPropagation();
            var menu = btn.parentElement;
            closeMenus(menu);
            menu.classList.toggle('open');
            return;
        }

        // Delete (removes the card from the page)
        var del = ev.target.closest('[data-delete]');
        if (del) {
            ev.preventDefault();
            if (window.confirm('Delete this submission?')) {
                del.closest('.article').remove();
                updateStats();
                render();
            }
        }
    });

    document.addEventListener('click', function () { closeMenus(null); });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') closeMenus(null);
    });

    // ---------- Breaking-news ticker ----------
    var text = document.querySelector('.breaking-text');
    var track = document.querySelector('.breaking-track');
    if (text && track) {
        var distance = text.scrollWidth + track.clientWidth;
        text.style.paddingLeft = track.clientWidth + 'px';
        text.style.animationDuration = Math.max(20, distance / 60) + 's';
    }

    updateStats();
    render();
});
