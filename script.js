document.addEventListener("DOMContentLoaded", () => {
    
    let userBookmarks = window.bookmarksData || [];

    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, character => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        })[character]);
    }

    function safeUrl(value) {
        const candidate = String(value ?? "#").trim();
        try {
            const parsed = new URL(candidate, window.location.href);
            return ["http:", "https:"].includes(parsed.protocol) ? escapeHTML(candidate) : "#";
        } catch (error) {
            return "#";
        }
    }

    let slideIndex = 0;
    let activeCategory = "All";
    const filterButtons = document.querySelectorAll(".filter");
    const searchInput = document.getElementById("searchInput");
    const articleGrid = document.getElementById("articleGrid");
    const noResults = document.getElementById("noResults");
    const bookmarkCountSpan = document.getElementById("bookmarkCount");

    function renderArticles() {
        if (!articleGrid) return;
        articleGrid.innerHTML = "";
        slideIndex = 0;

        const keyword = searchInput ? searchInput.value.toLowerCase().trim() : "";

        const filtered = userBookmarks.filter(item => {
            const matchesCategory = (activeCategory === "All") || 
                (item.category.toLowerCase() === activeCategory.toLowerCase());
            
            const matchesSearch = item.title.toLowerCase().includes(keyword) ||
                                  item.description.toLowerCase().includes(keyword) ||
                                  item.category.toLowerCase().includes(keyword);
            return matchesCategory && matchesSearch;
        });

        if (bookmarkCountSpan) {
            bookmarkCountSpan.textContent = userBookmarks.length;
        }

        const visibleCount = document.getElementById("visibleCount");
        if (visibleCount) visibleCount.textContent = String(filtered.length).padStart(2,"0");
        if (filtered.length === 0) {
            articleGrid.classList.add("hidden");
            if (noResults) noResults.classList.remove("hidden");
        } else {
            if (noResults) noResults.classList.add("hidden");
            articleGrid.classList.remove("hidden");

            filtered.forEach((article, index) => {
                const card = document.createElement("article");
                card.className = "article-card";
                card.style.animationDelay = `${index * 85}ms`;
                card.setAttribute("data-category", article.category);
                const isTech = article.category === "Technology" ? " tech" : "";

                card.innerHTML = `
                    <div class="image-wrap">
                        <img src="${safeUrl(article.image)}" alt="${escapeHTML(article.title)}" onerror="this.src='https://via.placeholder.com/400x200?text=NSBM+News'">
                        <span class="tag${isTech}">${escapeHTML(article.category)}</span>
                        <button class="bookmark saved" data-id="${escapeHTML(article.id)}" title="Remove bookmark">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="white" stroke="white" stroke-width="2"S>
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="content">
                        <p class="meta">${escapeHTML(article.meta)}</p>
                        <h3>${escapeHTML(article.title)}</h3>
                        <p class="description">${escapeHTML(article.description)}</p>
                        <div class="bottom">
                            <span class="author"><span class="avatar">${escapeHTML(article.authorCode)}</span> ${escapeHTML(article.authorName)}</span>
                            <a href="${safeUrl(article.readUrl)}" class="read">Read →</a>
                        </div>
                    </div>
                `;
                articleGrid.appendChild(card);
            });

            document.querySelectorAll(".bookmark").forEach(button => {
                button.addEventListener("click", async (e) => {
                    const id = e.currentTarget.getAttribute("data-id");
                    button.disabled = true;

                    try {
                        const response = await fetch("remove-bookmark.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ id, csrfToken: window.csrfToken })
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error("Bookmark removal failed");
                        }

                        const index = userBookmarks.findIndex(bookmark => bookmark.id === id);
                        if (index !== -1) {
                            userBookmarks.splice(index, 1);
                            renderArticles();
                        }
                    } catch (error) {
                        button.disabled = false;
                        window.alert("Could not remove bookmark. Please try again.");
                    }
                });
            });
        }

        positionCards();
        filterButtons.forEach(button => {
            const active = button.dataset.category === activeCategory;
            button.classList.toggle("active", active);
        });
    }

    filterButtons.forEach(button => {
        button.addEventListener("click", () => {
            activeCategory = button.dataset.category;
            renderArticles();
        });
    });

    if (searchInput) {
        searchInput.addEventListener("input", renderArticles);
    }

    function positionCards() {
        const cards = [...articleGrid.querySelectorAll(".article-card")];
        const width = articleGrid.clientWidth || window.innerWidth;
        const cardWidth = window.innerWidth < 650 ? 220 : 260;
        const spacing = window.innerWidth < 650 ? 188 : Math.min(292, Math.max(210, width * .205));
        const center = (cards.length - 1) / 2;
        // A symmetric, shallow U-shaped arc around the actual page center.
        cards.forEach((card, i) => {
            const relative = i - center - slideIndex;
            const angle = Math.max(-15, Math.min(15, relative * 5.1));
            const drop = Math.pow(relative, 2) * 20;
            card.style.setProperty('--x', `${relative * spacing}px`);
            card.style.setProperty('--y', `${drop}px`);
            card.style.setProperty('--angle', `${angle}deg`);
            card.style.zIndex = String(100 - Math.abs(relative) * 5);
            card.style.opacity = Math.abs(relative) > 4 ? '0' : '1';
            card.style.pointerEvents = Math.abs(relative) > 4 ? 'none' : 'auto';
        });
        const prev = document.getElementById('prevSlide');
        const next = document.getElementById('nextSlide');
        if(prev) prev.disabled = slideIndex <= -center;
        if(next) next.disabled = slideIndex >= center;
    }
    function moveSlide(direction) {
        const count = articleGrid.querySelectorAll('.article-card').length;
        const limit = (count - 1) / 2;
        slideIndex = Math.max(-limit, Math.min(limit, slideIndex + direction));
        positionCards();
    }
    window.addEventListener('resize', positionCards);
    document.getElementById("prevSlide")?.addEventListener("click", () => moveSlide(-1));
    document.getElementById("nextSlide")?.addEventListener("click", () => moveSlide(1));
    let touchX = 0;
    articleGrid.addEventListener("touchstart", e => {touchX=e.touches[0].clientX},{passive:true});
    articleGrid.addEventListener("touchend", e => {const dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>45)moveSlide(dx<0?1:-1)},{passive:true});
    renderArticles();
});

