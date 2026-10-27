function loadFragment(selector, path) {
    var target = document.querySelector(selector);
    if (!target) {
        return Promise.reject(new Error('Missing page fragment target: ' + selector));
    }

    return fetch(path).then(function (response) {
        if (!response.ok) {
            throw new Error('Unable to load ' + path + ': HTTP ' + response.status);
        }

        return response.text();
    }).then(function (markup) {
        var template = document.createElement('template');
        template.innerHTML = markup.trim();

        if (!template.content.firstElementChild) {
            throw new Error('The fragment is empty: ' + path);
        }

        target.replaceWith(template.content);
    });
}

function setCurrentNavigationLink() {
    var currentPage = window.location.pathname.split('/').pop() || 'index.html';

    document.querySelectorAll('.nav-link').forEach(function (link) {
        var linkPage = new URL(link.href).pathname.split('/').pop();
        var isCurrentPage = linkPage === currentPage;

        link.classList.toggle('active', isCurrentPage);
        if (isCurrentPage) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}

Promise.all([
    loadFragment('#site-header', 'header.html'),
    loadFragment('#site-footer', 'footer.html')
]).then(setCurrentNavigationLink).catch(function (error) {
    console.error('Unable to initialize the shared page layout.', error);
});
