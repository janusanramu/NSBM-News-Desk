document.addEventListener("DOMContentLoaded", () => {
    
    let userBookmarks = [
        {
            id: "b1",
            category: "Campus Life",
            image: "images/lecturehall.jpg",
            meta: "Oct 24, 2026 · 4 min read",
            title: "Annual Green Walk 2026 Announced with Massive Student Participation",
            description: "Join the largest campus event of the year celebrating sustainability, unity, and student spirit across all faculties at NSBM Green University.",
            authorCode: "JD",
            authorName: "Sahan Jayasinghe",
            readUrl: "#"
            
        },
        {
            id: "b2",
            category: "Sports",
            image: "images/sport.jpg",
            meta: "March 24, 2026 · 4 min read",
            title: "NSBM Sports Fiesta 2026",
            description: "Students showcased their sporting talents across 20 sports, celebrating teamwork, unity, and sportsmanship.",
            authorCode: "ST",
            authorName: "Kavindu Perera",
            readUrl: "#"
        },
        {
            id: "b3",
            category: "Arts & Culture",
            image: "images/culture.jpg",
            meta: "February 11, 2026 · 3 min read",
            title: "Harithabhinandana 2026 – A Celebration of Culture",
            description: "NSBM celebrated Sri Lankan culture and creativity through traditional performances, singing, drama, and student talent.",
            authorCode: "CS",
            authorName: "Anjal Mendis",
            readUrl: "#"
        },
        {
            id: "b4",
            category: "Technology",
            image: "images/Engineering.jpg",
            meta: "Oct 22, 2026 · 6 min read",
            title: "NSBM FIT Programme Introduces Students to Generative AI",
            description: "NSBM introduced Computing students to Generative AI, Agentic AI, and emerging technologies, exploring their applications in software engineering.",
            authorCode: "AS",
            authorName: "Tharindu Silva",
            readUrl: "#"
        }
    ];

    let activeCategory = "All";
    const filterButtons = document.querySelectorAll(".filter");
    const searchInput = document.getElementById("searchInput");
    const articleGrid = document.getElementById("articleGrid");
    const noResults = document.getElementById("noResults");
    const bookmarkCountSpan = document.getElementById("bookmarkCount");

    function renderArticles() {
        if (!articleGrid) return;
        articleGrid.innerHTML = "";

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

        if (filtered.length === 0) {
            articleGrid.classList.add("hidden");
            if (noResults) noResults.classList.remove("hidden");
        } else {
            if (noResults) noResults.classList.add("hidden");
            articleGrid.classList.remove("hidden");

            filtered.forEach(article => {
                const card = document.createElement("article");
                card.className = "article-card";
                card.setAttribute("data-category", article.category);
                const isTech = article.category === "Technology" ? " tech" : "";

                card.innerHTML = `
                    <div class="image-wrap">
                        <img src="${article.image}" alt="${article.title}" onerror="this.src='https://via.placeholder.com/400x200?text=NSBM+News'">
                        <span class="tag${isTech}">${article.category}</span>
                        <button class="bookmark saved" data-id="${article.id}" title="Remove bookmark">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="white" stroke="white" stroke-width="2"S>
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="content">
                        <p class="meta">${article.meta}</p>
                        <h3>${article.title}</h3>
                        <p class="description">${article.description}</p>
                        <div class="bottom">
                            <span class="author"><span class="avatar">${article.authorCode}</span> ${article.authorName}</span>
                            <a href="${article.readUrl}" class="read">Read →</a>
                        </div>
                    </div>
                `;
                articleGrid.appendChild(card);
            });

            document.querySelectorAll(".bookmark").forEach(button => {
                button.addEventListener("click", (e) => {
                    const id = e.currentTarget.getAttribute("data-id");
                    const index = userBookmarks.findIndex(b => b.id === id);
                    if (index !== -1) {
                        userBookmarks.splice(index, 1);
                        renderArticles();
                    }
                });
            });
        }

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

    renderArticles();
});
