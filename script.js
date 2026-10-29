
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
