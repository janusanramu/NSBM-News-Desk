document.addEventListener('DOMContentLoaded', function () {
    var PER_PAGE = 4;
    var currentPage = 1;
    var currentFilter = 'all';
    var list = document.getElementById('articleList');
    var filterSelect = document.getElementById('statusFilter');
    var pager = document.getElementById('pager');
    var showingText = document.getElementById('showingText');
    var emptyState = document.getElementById('emptyState');

    if (!list || !filterSelect || !pager || !showingText || !emptyState) {
        return;
    }

    function allArticles() {
        return Array.prototype.slice.call(list.querySelectorAll('.article'));
    }

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function updateStats() {
        var items = allArticles();
        var count = function (status) {
            return items.filter(function (article) {
                return article.dataset.status === status;
            }).length;
        };

        document.getElementById('statTotal').textContent = pad(items.length);
        document.getElementById('statApproved').textContent = pad(count('approved'));
        document.getElementById('statPending').textContent = pad(count('pending_review'));
        document.getElementById('statRejected').textContent = pad(count('rejected'));
    }

    function renderPager(totalPages) {
        pager.innerHTML = '';

        function addButton(label, page, options) {
            options = options || {};
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

        var from = matches.length ? start + 1 : 0;
        var to = Math.min(end, matches.length);
        showingText.textContent = 'Showing ' + from + '-' + to + ' of ' + matches.length + ' submissions';
        emptyState.style.display = matches.length ? 'none' : 'block';
        renderPager(totalPages);
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

    updateStats();
    render();
});
