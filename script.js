// ==========================================
// 1. NEWS SEARCH, CATEGORY FILTERING
//    AND LOAD MORE
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
  const categoryButtons = Array.from(
    document.querySelectorAll(".cat-btn")
  );

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
    "academics": ["computing", "business", "engineering"],
    "research": ["business"],
    "clubs & societies": ["events", "notices"],
    "clubs and societies": ["events", "notices"],
    "sports": ["sports"]
  };

  // Get article category from its link
  function getArticleCategory(card) {
    const link = card.querySelector(
      ".card-body h3 a, .read-link"
    );

    const href = (
      link?.getAttribute("href") || ""
    ).toLowerCase();

    if (href.includes("computing")) return "computing";
    if (href.includes("business")) return "business";
    if (href.includes("engineering")) return "engineering";
    if (href.includes("events")) return "events";
    if (href.includes("sports")) return "sports";
    if (href.includes("notices")) return "notices";

    return (
      card.dataset.category ||
      card.querySelector(".tag")?.textContent ||
      ""
    ).trim().toLowerCase();
  }

  // Assign categories to cards
  newsCards.forEach((card) => {
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
    contentGrid.parentNode.insertBefore(
      noResults,
      contentGrid
    );
  }

  // Update articles
  function updateArticles() {
    const term = searchInput?.value.trim().toLowerCase() || "";

    const allowedCategories =
      categoryMap[selectedCategory] || [selectedCategory];

    const matchingCards = newsCards.filter((card) => {
      const articleCategory = card.dataset.category;

      const matchesCategory =
        allowedCategories.includes("all") ||
        allowedCategories.includes(articleCategory);

      const matchesSearch =
        card.textContent.toLowerCase().includes(term);

      return matchesCategory && matchesSearch;
    });

    const limit = term ? matchingCards.length : visibleCount;

    // Hide all cards first
    newsCards.forEach((card) => {
      card.hidden = true;
      card.style.display = "none";
    });

    // Show matching cards
    matchingCards.forEach((card, index) => {
      if (index < limit) {
        card.hidden = false;
        card.style.display = "";
      }
    });

    // Show or hide no-results message
    noResults.hidden = matchingCards.length !== 0;

    // Load More button
    if (loadMoreButton) {
      loadMoreButton.hidden =
        term.length > 0 ||
        matchingCards.length <= visibleCount;
    }
  }

  // Category button events
  categoryButtons.forEach((button) => {
    button.addEventListener("click", () => {
      categoryButtons.forEach((item) => {
        item.classList.remove("active");
      });

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

  // Load More event
  loadMoreButton?.addEventListener("click", () => {
    visibleCount += batchSize;
    updateArticles();
  });

  // Initial display
  updateArticles();
});

// ==========================================
// 2. SUBSCRIBE TO NEWS CATEGORIES
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("subscriptionModal");

  if (!modal) return;

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

  const storageKey = "nsbmNewsDeskSubscriptions";

  // Get categories from HTML
  let categories = [];

  try {
    categories = JSON.parse(
      modal.dataset.categories || "[]"
    );
  } catch (error) {
    categories = [];
    console.error("Unable to load subscription categories:", error);
  }

  // Get saved preferences
  function getSavedCategories() {
    try {
      const saved = JSON.parse(
        localStorage.getItem(storageKey) || "[]"
      );

      return Array.isArray(saved) ? saved : [];
    } catch (error) {
      return [];
    }
  }

  // Display category checkboxes
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

  // Button events
  openButton?.addEventListener("click", openModal);

  closeButton?.addEventListener("click", closeModal);

  cancelButton?.addEventListener("click", closeModal);

  // Close when clicking outside
  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  // Close with Escape key
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) {
      closeModal();
    }
  });

  // Save subscription preferences
  saveButton?.addEventListener("click", () => {
    if (!optionsContainer || !feedback) return;

    const selected = Array.from(
      optionsContainer.querySelectorAll(
        'input[name="subscriptionCategory"]:checked'
      )
    ).map((checkbox) => checkbox.value);

    if (selected.length === 0) {
      feedback.textContent =
        "Please select at least one category.";

      return;
    }

    localStorage.setItem(
      storageKey,
      JSON.stringify(selected)
    );

    feedback.textContent =
      "Your subscription preferences have been saved!";

    window.setTimeout(() => {
      if (!modal.hidden) {
        closeModal();
      }
    }, 1000);
  });
});

// ==========================================
// 3. NEWS BOOKMARK SYSTEM
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
  const STORAGE_KEY = "nsbmNewsDeskBookmarks";

  const bookmarkButtons = document.querySelectorAll(
    ".card .bookmark-btn"
  );

  // Get saved bookmarks
  function getBookmarks() {
    try {
      const data = JSON.parse(
        localStorage.getItem(STORAGE_KEY) || "[]"
      );

      return Array.isArray(data) ? data : [];
    } catch (error) {
      return [];
    }
  }

  // Save bookmarks
  function saveBookmarks(bookmarks) {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify(bookmarks)
    );
  }

  // Get article information
  function getArticleData(card) {
    const titleLink = card.querySelector(
      ".card-body h3 a"
    );

    const image = card.querySelector(
      ".card-image img"
    );

    const author = card.querySelector(
      ".card-meta span:first-child"
    );

    const date = card.querySelector(
      ".card-meta span:last-child"
    );

    const description = card.querySelector(
      ".card-body p"
    );

    const tag = card.querySelector(".tag");

    return {
      id:
        titleLink?.getAttribute("href") ||
        titleLink?.textContent.trim(),

      title:
        titleLink?.textContent.trim() ||
        "Untitled News",

      link:
        titleLink?.getAttribute("href") || "#",

      image:
        image?.getAttribute("src") || "",

      imageAlt:
        image?.alt || "",

      author:
        author?.textContent.trim() || "",

      date:
        date?.textContent.trim() || "",

      description:
        description?.textContent.trim() || "",

      category:
        tag?.textContent.trim() || ""
    };
  }

  // Update bookmark button
  function updateBookmarkButton(button, isSaved) {
    button.classList.toggle("saved", isSaved);

    button.setAttribute(
      "aria-pressed",
      String(isSaved)
    );

    button.setAttribute(
      "aria-label",
      isSaved
        ? "Remove bookmark"
        : "Bookmark article"
    );
  }

  // Initialize bookmark buttons
  bookmarkButtons.forEach((button) => {
    const card = button.closest(".card");

    if (!card) return;

    const article = getArticleData(card);

    const saved = getBookmarks().some(
      (item) => item.id === article.id
    );

    updateBookmarkButton(button, saved);

    // Bookmark click event
    button.addEventListener("click", () => {
      let bookmarks = getBookmarks();

      const alreadySaved = bookmarks.some(
        (item) => item.id === article.id
      );

      if (alreadySaved) {
        bookmarks = bookmarks.filter(
          (item) => item.id !== article.id
        );
      } else {
        bookmarks.push(article);
      }

      saveBookmarks(bookmarks);

      updateBookmarkButton(
        button,
        !alreadySaved
      );
    });
  });
});