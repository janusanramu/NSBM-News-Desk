
document.addEventListener("DOMContentLoaded", () => {
  const categoryButtons = document.querySelectorAll(".cat-btn");
  const newsGrid = document.querySelector(".card-grid");
  const searchInput = document.querySelector(
    '.search-box input[type="search"]'
  );
  const loadMoreButton = document.querySelector(".btn-load-more");
  const noticeCards = Array.from(document.querySelectorAll(".notice"));
  const newsCards = newsGrid
    ? Array.from(newsGrid.querySelectorAll(".card"))
    : [];

  const noResults = document.createElement("p");
  noResults.className = "search-no-results";
  noResults.textContent = "No matching news or announcements found.";
  noResults.hidden = true;

  const contentGrid = document.querySelector(".content-grid");
  if (contentGrid?.parentNode) {
    contentGrid.parentNode.insertBefore(noResults, contentGrid);
  }

  let selectedCategory = "all news";
  let visibleCount = 4;
  const batchSize = 4;

  // Search and category filtering
  function updateArticles() {
    const term = searchInput?.value.trim().toLowerCase() || "";
    const noticesCategory = selectedCategory === "notices";
    let matchCount = 0;

    newsCards.forEach((card, index) => {
      const tag =
        card.querySelector(".tag")?.textContent.trim().toLowerCase() || "";

      const matchesCategory =
        !noticesCategory &&
        (selectedCategory === "all news" || tag === selectedCategory);

      const matchesSearch = card.textContent.toLowerCase().includes(term);
      const show = matchesCategory && matchesSearch && index < visibleCount;

      card.hidden = !show;

      if (matchesCategory && matchesSearch) matchCount++;
    });

    noticeCards.forEach((notice) => {
      const matchesSearch = notice.textContent.toLowerCase().includes(term);
      const show = matchesSearch && (noticesCategory || selectedCategory === "all news");

      notice.hidden = !show;

      if (show) matchCount++;
    });

    noResults.hidden = !(term && matchCount === 0);

    if (loadMoreButton) {
      const hasMore = newsCards.some((card, index) => {
        const tag =
          card.querySelector(".tag")?.textContent.trim().toLowerCase() || "";

        return (
          index >= visibleCount &&
          selectedCategory !== "notices" &&
          (selectedCategory === "all news" || tag === selectedCategory) &&
          card.textContent.toLowerCase().includes(term)
        );
      });

      loadMoreButton.hidden = !hasMore;
    }
  }

  categoryButtons.forEach((button) => {
    button.addEventListener("click", () => {
      categoryButtons.forEach((item) => item.classList.remove("active"));
      button.classList.add("active");

      const category = button.textContent.trim().toLowerCase();
      selectedCategory = category.includes("all") ? "all news" : category;

      visibleCount = 4;
      updateArticles();
    });
  });

  searchInput?.addEventListener("input", updateArticles);

  loadMoreButton?.addEventListener("click", () => {
    visibleCount = Math.min(visibleCount + batchSize, newsCards.length);
    updateArticles();
  });

  // Student profile dropdown
  const profileToggle = document.getElementById("profileToggle");
  const profileDropdown = document.getElementById("profileDropdown");

  if (profileToggle && profileDropdown) {
    profileDropdown.hidden = true;
    profileToggle.setAttribute("aria-expanded", "false");

    profileToggle.addEventListener("click", (event) => {
      event.stopPropagation();

      const open = profileDropdown.hidden;
      profileDropdown.hidden = !open;
      profileToggle.setAttribute("aria-expanded", String(open));
    });

    profileDropdown.addEventListener("click", (event) => {
      event.stopPropagation();
    });

    document.addEventListener("click", (event) => {
      if (!event.target.closest(".profile-menu")) {
        profileDropdown.hidden = true;
        profileToggle.setAttribute("aria-expanded", "false");
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        profileDropdown.hidden = true;
        profileToggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  // Logout confirmation
  document.getElementById("logoutBtn")?.addEventListener("click", (event) => {
    event.preventDefault();

    if (confirm("Are you sure you want to log out?")) {
      window.location.href = "index.html";
    }
  });

  // Save bookmarks in this browser
  const storageKey = "nsbmNewsDeskBookmarks";

  function readBookmarks() {
    try {
      const saved = JSON.parse(localStorage.getItem(storageKey) || "[]");
      return Array.isArray(saved) ? saved : [];
    } catch {
      return [];
    }
  }

  function articleKey(card) {
    return (
      card.querySelector(".card-body h3")?.textContent.trim() ||
      card.querySelector(".hero-title")?.textContent.trim() ||
      ""
    );
  }

  function setBookmark(button, saved) {
    button.classList.toggle("active", saved);
    button.setAttribute("aria-pressed", String(saved));
    button.style.backgroundColor = saved ? "#026838" : "#f1f5f9";
    button.style.color = saved ? "#ffffff" : "#64748b";
    button.setAttribute(
      "aria-label",
      saved ? "Remove bookmark" : "Bookmark article"
    );
  }

  const bookmarkItems = [
    ...newsCards.map((card) => ({
      card,
      button: card.querySelector(".bookmark-btn"),
      key: articleKey(card)
    }))
  ];

  const heroBookmark = document.querySelector(".hero-actions .btn-secondary");

  if (heroBookmark) {
    bookmarkItems.push({
      card: heroBookmark.closest(".hero-feature"),
      button: heroBookmark,
      key:
        heroBookmark.closest(".hero-feature")
          ?.querySelector(".hero-title")?.textContent.trim() || ""
    });
  }

  function syncBookmarks() {
    const saved = readBookmarks();

    bookmarkItems.forEach(({ button, key }) => {
      if (button && key) setBookmark(button, saved.includes(key));
    });
  }

  bookmarkItems.forEach(({ button, key }) => {
    if (!button || !key) return;

    button.addEventListener("click", (event) => {
      event.preventDefault();

      const saved = readBookmarks();
      const isSaved = saved.includes(key);
      const updated = isSaved
        ? saved.filter((item) => item !== key)
        : [...saved, key];

      try {
        localStorage.setItem(storageKey, JSON.stringify(updated));
        syncBookmarks();
      } catch {
        alert("Bookmarks could not be saved in this browser.");
      }
    });
  });

  // Subscribe Feed: visual toggle only, not a real notification service.
  const subscribeButton = document.querySelector(".subscribe-btn");

  if (subscribeButton) {
    subscribeButton.setAttribute("aria-pressed", "false");

    subscribeButton.addEventListener("click", () => {
      const enabled =
        subscribeButton.getAttribute("aria-pressed") !== "true";

      subscribeButton.setAttribute("aria-pressed", String(enabled));
      subscribeButton.classList.toggle("alerts-enabled", enabled);
      subscribeButton.lastChild.textContent = enabled
        ? " Feed Subscribed"
        : " Subscribe Feed";
    });
  }

  syncBookmarks();
  updateArticles();
});
