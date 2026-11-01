/* ==========================================
   NSBM NEWSDESK - STUDENT PORTAL
   PART 1: SEARCH, CATEGORY FILTER & LOAD MORE
========================================== */

document.addEventListener("DOMContentLoaded", () => {

    const categoryButtons = document.querySelectorAll(".cat-btn");
    const searchInput = document.querySelector(
        '.search-box input[type="search"]'
    );

    const newsGrid = document.querySelector(".card-grid");
    const newsCards = newsGrid
        ? Array.from(newsGrid.querySelectorAll(".card"))
        : [];

    const loadMoreButton = document.querySelector(".btn-load-more");
    const contentGrid = document.querySelector(".content-grid");

    // Category mapping
    const categoryMap = {
        "all news": ["all"],
        "campus life": ["events"],
        "academics": ["computing", "engineering"],
        "research": ["business"],
        "clubs & societies": ["events", "notices"],
        "clubs and societies": ["events", "notices"],
        "sports": ["sports"]
    };

    // Get category from article link
    function getArticleCategory(card) {

        const link = card.querySelector(".card-body h3 a");
        const href = (link?.getAttribute("href") || "").toLowerCase();

        if (href.includes("computing")) return "computing";
        if (href.includes("business")) return "business";
        if (href.includes("engineering")) return "engineering";
        if (href.includes("events")) return "events";
        if (href.includes("sports")) return "sports";
        if (href.includes("notices")) return "notices";

        return (card.dataset.category || "").toLowerCase();
    }

    // Store categories
    newsCards.forEach(card => {
        card.dataset.category = getArticleCategory(card);
    });

    let selectedCategory = "all news";
    let visibleCount = 4;
    const batchSize = 4;

    // No results message
    const noResults = document.createElement("p");

    noResults.className = "search-no-results";
    noResults.textContent =
        "No matching news found. Try another category or keyword.";

    noResults.hidden = true;

    if (contentGrid?.parentNode) {
        contentGrid.parentNode.insertBefore(noResults, contentGrid);
    }

    // Update news articles
    function updateArticles() {

        const searchTerm = searchInput?.value.trim().toLowerCase() || "";

        const allowedCategories =
            categoryMap[selectedCategory] || [selectedCategory];

        const matchingCards = newsCards.filter(card => {

            const articleCategory = card.dataset.category;

            const matchesCategory =
                allowedCategories.includes("all") ||
                allowedCategories.includes(articleCategory);

            const matchesSearch =
                card.textContent.toLowerCase().includes(searchTerm);

            return matchesCategory && matchesSearch;
        });

        const limit = searchTerm ? matchingCards.length : visibleCount;

        newsCards.forEach(card => {
            card.hidden = true;
            card.style.display = "none";
        });

        matchingCards.forEach((card, index) => {

            if (index < limit) {
                card.hidden = false;
                card.style.display = "";
            }

        });

        noResults.hidden = matchingCards.length !== 0;

        if (loadMoreButton) {
            loadMoreButton.hidden =
                searchTerm.length > 0 ||
                matchingCards.length <= visibleCount;
        }
    }

    // Category button events
    categoryButtons.forEach(button => {

        button.addEventListener("click", () => {

            categoryButtons.forEach(item =>
                item.classList.remove("active")
            );

            button.classList.add("active");

            selectedCategory =
                button.textContent.trim().toLowerCase();

            visibleCount = 4;

            updateArticles();
        });

    });

    // Search event
    searchInput?.addEventListener("input", () => {

        visibleCount = 4;
        updateArticles();

    });

    // Load more event
    loadMoreButton?.addEventListener("click", () => {

        visibleCount += batchSize;
        updateArticles();

    });

    // Initial display
    updateArticles();

    /* ==========================================
   PART 2: BOOKMARKS, PROFILE & LOGOUT
========================================== */

    // Student profile dropdown
    const profileToggle = document.getElementById("profileToggle");
    const profileDropdown = document.getElementById("profileDropdown");

    if (profileToggle && profileDropdown) {

        profileDropdown.hidden = true;

        profileToggle.setAttribute("aria-expanded", "false");

        profileToggle.addEventListener("click", event => {

            event.stopPropagation();

            const isOpen = profileDropdown.hidden;

            profileDropdown.hidden = !isOpen;

            profileToggle.setAttribute(
                "aria-expanded",
                String(isOpen)
            );

        });

        profileDropdown.addEventListener("click", event => {
            event.stopPropagation();
        });

        document.addEventListener("click", event => {

            if (!event.target.closest(".profile-menu")) {

                profileDropdown.hidden = true;

                profileToggle.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        });

        document.addEventListener("keydown", event => {

            if (event.key === "Escape") {

                profileDropdown.hidden = true;

                profileToggle.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        });

    }


    // Logout confirmation
    document.getElementById("logoutBtn")?.addEventListener(
        "click",
        event => {

            event.preventDefault();

            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "index.html";
            }

        }
    );


    // BOOKMARK SYSTEM
    const storageKey = "nsbmNewsDeskBookmarks";

    // Read saved bookmarks
    function readBookmarks() {

        try {

            const saved = JSON.parse(
                localStorage.getItem(storageKey) || "[]"
            );

            return Array.isArray(saved) ? saved : [];

        } catch (error) {

            console.error("Bookmark loading error:", error);

            return [];

        }

    }


    // Get unique article title
    function articleKey(card) {

        return (
            card.querySelector(".card-body h3")
                ?.textContent.trim() ||
            card.querySelector(".hero-title")
                ?.textContent.trim() ||
            ""
        );

    }


    // Update bookmark button appearance
    function setBookmark(button, saved) {

        button.classList.toggle("active", saved);
        button.classList.toggle("saved", saved);

        button.setAttribute("aria-pressed", String(saved));

        button.style.backgroundColor = saved
            ? "#026838"
            : "#f1f5f9";

        button.style.color = saved
            ? "#ffffff"
            : "#64748b";

        button.setAttribute(
            "aria-label",
            saved ? "Remove bookmark" : "Bookmark article"
        );

        button.title = saved
            ? "Remove bookmark"
            : "Bookmark article";

    }


    // Collect news card bookmark buttons
    const bookmarkItems = newsCards.map(card => ({

        card: card,

        button: card.querySelector(".bookmark-btn"),

        key: articleKey(card)

    }));


    // Featured story bookmark
    const heroBookmark = document.querySelector(
        ".hero-actions .btn-secondary"
    );

    if (heroBookmark) {

        bookmarkItems.push({

            card: heroBookmark.closest(".hero-feature"),

            button: heroBookmark,

            key: heroBookmark.closest(".hero-feature")
                ?.querySelector(".hero-title")
                ?.textContent.trim() || ""

        });

    }


    // Synchronize saved bookmark status
    function syncBookmarks() {

        const saved = readBookmarks();

        bookmarkItems.forEach(({ button, key }) => {

            if (button && key) {

                setBookmark(button, saved.includes(key));

            }

        });

    }


    // Bookmark button click events
    bookmarkItems.forEach(({ button, key }) => {

        if (!button || !key) return;

        button.addEventListener("click", event => {

            event.preventDefault();

            let saved = readBookmarks();

            const alreadySaved = saved.includes(key);

            if (alreadySaved) {

                saved = saved.filter(item => item !== key);

            } else {

                saved.push(key);

            }

            try {

                localStorage.setItem(
                    storageKey,
                    JSON.stringify(saved)
                );

                syncBookmarks();

            } catch (error) {

                alert("Bookmarks could not be saved in this browser.");

                console.error(error);

            }

        });

    });


    // Initial bookmark status
    syncBookmarks();

    /* ==========================================
   PART 3: SUBSCRIBE ALERTS
========================================== */

    const modal = document.getElementById("subscriptionModal");

    if (modal) {

        const openButton =
            document.getElementById("subscribeAlertsBtn") ||
            document.getElementById("subscribeFeedBtn");

        const optionsContainer =
            document.getElementById("subscriptionOptions");

        const feedback =
            document.getElementById("subscriptionFeedback");

        const saveButton =
            document.getElementById("saveSubscription");

        const closeButton =
            document.getElementById("closeSubscriptionModal");

        const cancelButton =
            document.getElementById("cancelSubscription");

        const subscriptionStorageKey =
            "nsbmNewsDeskSubscriptions";

        let categories = [];

        try {

            categories = JSON.parse(
                modal.dataset.categories || "[]"
            );

        } catch (error) {

            categories = [];

        }


        // Get saved preferences
        function getSavedCategories() {

            try {

                const saved = JSON.parse(
                    localStorage.getItem(subscriptionStorageKey) || "[]"
                );

                return Array.isArray(saved) ? saved : [];

            } catch (error) {

                return [];

            }

        }


        // Display category options
        function renderOptions() {

            if (!optionsContainer) return;

            const saved = getSavedCategories();

            optionsContainer.innerHTML = "";

            categories.forEach((category, index) => {

                const label = document.createElement("label");

                label.className = "subscription-option";

                const checkbox = document.createElement("input");

                checkbox.type = "checkbox";
                checkbox.name = "subscriptionCategory";
                checkbox.value = category;
                checkbox.checked = saved.includes(category);

                checkbox.id = `subscription-category-${index}`;

                const text = document.createElement("span");

                text.textContent = category;

                label.append(checkbox, text);

                optionsContainer.appendChild(label);

            });

        }


        // Open modal
        function openModal() {

            renderOptions();

            if (feedback) {
                feedback.textContent = "";
            }

            modal.hidden = false;

            document.body.classList.add("subscription-open");

            closeButton?.focus();

        }


        // Close modal
        function closeModal() {

            modal.hidden = true;

            document.body.classList.remove("subscription-open");

            openButton?.focus();

        }


        // Open and close events
        openButton?.addEventListener("click", openModal);

        closeButton?.addEventListener("click", closeModal);

        cancelButton?.addEventListener("click", closeModal);


        modal.addEventListener("click", event => {

            if (event.target === modal) {
                closeModal();
            }

        });


        document.addEventListener("keydown", event => {

            if (event.key === "Escape" && !modal.hidden) {
                closeModal();
            }

        });


        // Save preferences
        saveButton?.addEventListener("click", () => {

            const selected = Array.from(
                optionsContainer.querySelectorAll(
                    'input[name="subscriptionCategory"]:checked'
                )
            ).map(checkbox => checkbox.value);


            if (selected.length === 0) {

                if (feedback) {
                    feedback.textContent =
                        "Please select at least one category.";
                }

                return;

            }


            try {

                localStorage.setItem(
                    subscriptionStorageKey,
                    JSON.stringify(selected)
                );

                if (feedback) {

                    feedback.textContent =
                        "Your subscription preferences have been saved!";

                }

                window.setTimeout(() => {

                    if (!modal.hidden) {
                        closeModal();
                    }

                }, 1000);

            } catch (error) {

                if (feedback) {
                    feedback.textContent =
                        "Unable to save preferences. Please try again.";
                }

            }

        });

    }


    // Initial page setup
    updateArticles();

    syncBookmarks();

}); // END DOMContentLoaded