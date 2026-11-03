
function updateArticles() {
  const searchTerm = searchInput?.value.trim().toLowerCase() || "";
  let matchCount = 0;

  newsCards.forEach((card) => {
    const tag =
      card.querySelector(".tag")?.textContent.trim().toLowerCase() || "";

    const matchesCategory =
      selectedCategory === "all news" ||
      tag === selectedCategory ||
      tag.includes(selectedCategory);

    const matchesSearch =
      card.textContent.toLowerCase().includes(searchTerm);

    const shouldShow = matchesCategory && matchesSearch;

    card.hidden = !shouldShow;

    if (shouldShow) matchCount++;
  });

  noticeCards.forEach((notice) => {
    const matchesSearch =
      notice.textContent.toLowerCase().includes(searchTerm);

    notice.hidden = !matchesSearch;

    if (matchesSearch) matchCount++;
  });

  noResults.hidden = !(searchTerm && matchCount === 0);

  if (loadMoreButton) {
    loadMoreButton.hidden = true;
  }
}



// Subscribe to selected news categories (saved in this browser for the demo).
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("subscriptionModal");
  if (!modal) return;

  const openButton =
    document.getElementById("subscribeAlertsBtn") ||
    document.getElementById("subscribeFeedBtn");
  const optionsContainer = document.getElementById("subscriptionOptions");
  const feedback = document.getElementById("subscriptionFeedback");
  const saveButton = document.getElementById("saveSubscription");
  const closeButton = document.getElementById("closeSubscriptionModal");
  const cancelButton = document.getElementById("cancelSubscription");
  const storageKey = "nsbmNewsDeskSubscriptions";

  let categories = [];
  try {
    categories = JSON.parse(modal.dataset.categories || "[]");
  } catch {
    categories = [];
  }

  function getSavedCategories() {
    try {
      const saved = JSON.parse(localStorage.getItem(storageKey) || "[]");
      return Array.isArray(saved) ? saved : [];
    } catch {
      return [];
    }
  }

  function renderOptions() {
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

  function openModal() {
    renderOptions();
    feedback.textContent = "";
    modal.hidden = false;
    document.body.classList.add("subscription-open");
    closeButton.focus();
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove("subscription-open");
    openButton?.focus();
  }

  openButton?.addEventListener("click", openModal);
  closeButton?.addEventListener("click", closeModal);
  cancelButton?.addEventListener("click", closeModal);

  modal.addEventListener("click", (event) => {
    if (event.target === modal) closeModal();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modal.hidden) closeModal();
  });

  saveButton?.addEventListener("click", () => {
    const selected = Array.from(
      optionsContainer.querySelectorAll('input[name="subscriptionCategory"]:checked')
    ).map((checkbox) => checkbox.value);

    if (selected.length === 0) {
      feedback.textContent = "Please select at least one category.";
      return;
    }

    localStorage.setItem(storageKey, JSON.stringify(selected));
    feedback.textContent = "Your subscription preferences have been saved!";
    window.setTimeout(() => {
      if (!modal.hidden) closeModal();
    }, 1000);
  });
});
