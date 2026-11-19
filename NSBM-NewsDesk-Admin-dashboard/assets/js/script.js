// Articles and announcements are loaded from MySQL by the integration layer.
let articles = [];
let announcements = [];
function saveData() { document.dispatchEvent(new Event('nsbm-admin-changed')); }

const $ = id => document.getElementById(id);
let statusChart = null;
let categoryChart = null;
let toastTimer;

function showToast(message) {
    $("toast").textContent = message;
    $("toast").classList.add("show");

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        $("toast").classList.remove("show");
    }, 2800);
}

function openModal(title, content) {
    $("modalTitle").textContent = title;
    $("modalContent").replaceChildren(content);
    $("modalBackdrop").classList.remove("hidden");
}

function closeModal() {
    $("modalBackdrop").classList.add("hidden");
}

function element(tag, text = "", className = "") {
    const el = document.createElement(tag);
    el.textContent = text;
    el.className = className;
    return el;
}

function button(text, className, action) {
    const el = element("button", text, className);
    el.type = "button";
    el.addEventListener("click", action);
    return el;
}

function badge(text, status = "") {
    return element(
        "span",
        text,
        `badge ${status ? "status-badge " + status.toLowerCase() : ""}`
    );
}

/* NAVIGATION */
function navigate(page) {
    const validPages = [
        "dashboard", "articles", "categories",
        "announcements", "reports", "comments", "admin-management", "student-approvals", "settings"
    ];

    if (!validPages.includes(page)) page = "dashboard";

    document.querySelectorAll(".page-section").forEach(section => {
        section.classList.add("hidden");
    });

    $(`${page}Page`).classList.remove("hidden");

    document.querySelectorAll(".side-link").forEach(link => {
        const active = link.dataset.page === page;
        link.classList.toggle("active", active);
        if (active) link.setAttribute("aria-current", "page");
        else link.removeAttribute("aria-current");
    });

    if (page === "dashboard") renderDashboard();
    if (page === "articles") renderArticles();
    if (page === "categories") renderCategories();
    if (page === "announcements") renderAnnouncements();
    if (page === "reports") renderReports();
    if (page === "comments") renderComments();
    if (page === "admin-management" && window.loadAdminAccounts) window.loadAdminAccounts();
}

document.querySelectorAll(".side-link").forEach(link => {
    link.addEventListener("click", event => {
        event.preventDefault();
        const page = link.dataset.page;
        history.replaceState(null, "", `#${page}`);
        navigate(page);
    });
});

window.addEventListener("hashchange", () => {
    navigate(location.hash.slice(1) || "dashboard");
});

/* ARTICLE TABLE */
function authorLink(author, id) {
    const article = articles.find(a => a.id === id);
    const link = document.createElement("button");
    link.type = "button";
    link.className = "author-person";
    link.title = `View ${author}'s profile`;
    const avatar = document.createElement("span");
    avatar.className = "author-photo";
    const initials = (author || "A").trim().split(/\s+/).slice(0, 2).map(s => s[0] || "").join("").toUpperCase();
    avatar.textContent = initials;
    if (article && article.avatar) {
        const img = document.createElement("img");
        img.src = article.avatar;
        img.alt = "";
        img.loading = "lazy";
        img.onerror = () => img.remove();
        avatar.append(img);
    }
    const name = document.createElement("span");
    name.className = "author-person-name";
    name.textContent = author;
    link.append(avatar, name);
    link.addEventListener("click", event => { event.stopPropagation(); showAuthor(id); });
    return link;
}

function showAuthor(id) {
    const article = articles.find(a => a.id === id);
    if (!article) return;
    const author = article.author;
    const authored = articles.filter(a => a.author === author);
    const content = element("div", "", "author-profile-modal");
    const avatar = element("div", (author.trim().split(/\s+/).map(part => part[0]).slice(0,2).join("") || "A").toUpperCase(), "author-avatar");
        if (article.avatar) {
        const initials = avatar.textContent;
        const photo = document.createElement("img");
        photo.src = article.avatar;
        photo.alt = "";
        photo.style.cssText = "width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block";
        photo.onerror = () => { photo.remove(); avatar.textContent = initials; };
        avatar.textContent = "";
        avatar.append(photo);
    }
    content.append(avatar, element("h3", author, "author-profile-name"));
    content.append(element("p", "NSBM NewsDesk contributor", "author-subtitle"));
    const info = element("div", "", "author-info-grid");
    const details = [
      ["Full name", author], ["Batch", article.author_intake || article.batch || "Not provided"],
      ["Department", [article.author_faculty, article.author_degree].filter(Boolean).join(" • ") || article.department || "Not provided"], ["Email address", article.author_email || article.email || "Not provided"],
      ["Total posts", authored.length], ["Approved posts", authored.filter(a => a.status === "Published").length],
      ["Rejected posts", authored.filter(a => a.status === "Rejected").length]
    ];
    details.forEach(([label, value]) => { const item = element("div", "", "author-info-item"); item.append(element("small", label), element("strong", String(value))); info.append(item); });
    content.append(info, button("Close", "secondary-button", closeModal));
    openModal("Author Profile", content);
}

// Single accessible floating menu, never nested inside table rows.
let currentActionsButton = null;
const actionMenu = document.createElement("div");
actionMenu.id = "nsbmArticleMenu";
actionMenu.setAttribute("role", "menu");
actionMenu.hidden = true;
document.body.appendChild(actionMenu);

function closeArticleActions() {
    actionMenu.hidden = true;
    actionMenu.style.visibility = "hidden";
    if (currentActionsButton) currentActionsButton.setAttribute("aria-expanded", "false");
    currentActionsButton = null;
}
function positionArticleActions() {
    if (!currentActionsButton || actionMenu.hidden) return;
    const anchor = currentActionsButton.getBoundingClientRect();
    const menuWidth = actionMenu.offsetWidth || 210;
    const menuHeight = actionMenu.offsetHeight || 190;
    const x = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, anchor.right - menuWidth));
    const y = window.innerHeight - anchor.bottom >= menuHeight + 12
        ? anchor.bottom + 6 : Math.max(8, anchor.top - menuHeight - 6);
    actionMenu.style.left = x + "px";
    actionMenu.style.top = y + "px";
    actionMenu.style.visibility = "visible";
}
function showArticleActions(article, trigger) {
    if (currentActionsButton === trigger && !actionMenu.hidden) {
        closeArticleActions(); return;
    }
    closeArticleActions();
    currentActionsButton = trigger;
    trigger.setAttribute("aria-expanded", "true");
    actionMenu.replaceChildren();
    const option = (label, kind, fn) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "nsbm-action-option " + kind;
        item.setAttribute("role", "menuitem");
        item.textContent = label;
        item.addEventListener("click", e => {
            e.stopPropagation();
            closeArticleActions();
            fn(article.id);
        });
        actionMenu.appendChild(item);
    };
    option("Edit article", "", editArticle);
    if (article.status === "Pending") {
        option("Approve", "approve", approveArticle);
        option("Reject", "danger", rejectArticle);
    }
    option("Remove", "danger", removeArticle);
    actionMenu.hidden = false;
    actionMenu.style.visibility = "hidden";
    positionArticleActions();
}
function createActions(article) {
    const group = element("div", "", "tidy-actions");
    group.append(button("View", "table-view-button", () => {
        closeArticleActions(); viewArticle(article.id);
    }));
    const trigger = button("Actions ▾", "table-more-button nsbm-actions-trigger", () => {
        showArticleActions(article, trigger);
    });
    trigger.setAttribute("aria-haspopup", "menu");
    trigger.setAttribute("aria-expanded", "false");
    trigger.setAttribute("aria-label", "Actions for " + article.title);
    group.append(trigger);
    return group;
}
document.addEventListener("click", e => {
    if (!actionMenu.hidden && !actionMenu.contains(e.target) && !e.target.closest(".nsbm-actions-trigger")) closeArticleActions();
});
document.addEventListener("keydown", e => {
    if (e.key === "Escape" && !actionMenu.hidden) {
        const buttonToFocus = currentActionsButton;
        closeArticleActions(); buttonToFocus?.focus();
    }
});
window.addEventListener("resize", positionArticleActions);
// Scrolling changes the anchor position; close rather than leave a detached menu.
window.addEventListener("scroll", () => { if (!actionMenu.hidden) closeArticleActions(); }, true);

function renderDashboard() {
    const pending = articles.filter(a => a.status === "Pending");

    $("totalArticles").textContent = articles.length;
    $("pendingCount").textContent = pending.length;
    $("pendingLabel").textContent = `${pending.length} Pending`;
    const totalTrack = $("totalArticleTrack"); if(totalTrack) totalTrack.style.width = articles.length ? "100%" : "0%";
    const pendingTrack = $("pendingArticleTrack"); if(pendingTrack) pendingTrack.style.width = articles.length ? `${pending.length / articles.length * 100}%` : "0%";

    const tbody = $("pendingTable");
    tbody.replaceChildren();

    if (pending.length === 0) {
        const row = element("tr");
        const cell = element("td", "No pending submissions.");
        cell.colSpan = 4;
        row.append(cell);
        tbody.append(row);
        return;
    }

    pending.forEach(article => {
        const row = document.createElement("tr");

        const titleCell = element("td");
        titleCell.append(element("span", article.title, "article-title"));
        row.append(titleCell);
        const authorCell = element("td"); authorCell.append(authorLink(article.author, article.id)); row.append(authorCell);

        const category = document.createElement("td");
        category.append(badge(article.category));
        row.append(category);

        row.append(element("td", article.date));

        row.classList.add("clickable-overview-row");
        row.tabIndex = 0;
        row.setAttribute("role", "link");
        row.setAttribute("aria-label", `Open ${article.title} in Manage Articles`);
        const openArticles = () => {
            $("articleSearch").value = article.title;
            $("statusFilter").value = "all";
            location.hash = "articles";
            navigate("articles");
            $("articleSearch").focus();
        };
        row.addEventListener("click", openArticles);
        row.addEventListener("keydown", event => {
            if (event.key === "Enter" || event.key === " ") { event.preventDefault(); openArticles(); }
        });

        tbody.append(row);
    });
}

function renderArticles() {
    closeArticleActions();
    const tbody = $("articlesTable");
    tbody.replaceChildren();

    const search = $("articleSearch").value.trim().toLowerCase();
    const status = $("statusFilter").value;

    const results = articles.filter(article => {
        const matchesText =
            article.title.toLowerCase().includes(search) ||
            article.author.toLowerCase().includes(search) ||
            article.category.toLowerCase().includes(search);

        return matchesText && (status === "all" || article.status === status);
    });

    $("emptyArticles").classList.toggle("hidden", results.length > 0);

    results.forEach(article => {
        const row = document.createElement("tr");

        row.append(element("td", article.title));
        const authorCell = element("td"); authorCell.append(authorLink(article.author, article.id)); row.append(authorCell);

        const category = document.createElement("td");
        category.append(badge(article.category));
        row.append(category);

        row.append(element("td", article.date));

        const statusCell = document.createElement("td");
        statusCell.append(badge(article.status, article.status));
        row.append(statusCell);

        const actions = document.createElement("td");
        actions.append(createActions(article));
        row.append(actions);

        tbody.append(row);
    });
}

function viewArticle(id) {
    const article = articles.find(a => a.id === id);
    if (!article) return;

    const content = document.createElement("div");

    [
        ["Title", article.title],
        ["Author", article.author],
        ["Category", article.category],
        ["Submitted date", article.date],
        ["Status", article.status],
        ["Details", article.description],
        ["Rejection reason", article.reason || "Not applicable"]
    ].forEach(([label, value]) => {
        const field = element("div", "", "modal-field");
        field.append(element("strong", label));
        field.append(element("p", value, "modal-content-text"));
        content.append(field);
    });

    const actions = element("div", "", "modal-actions");
    actions.append(button("Edit Article", "primary-button", () => editArticle(id)));
    actions.append(button("Remove Article", "danger-button", () => removeArticle(id)));
    actions.append(button("Close", "secondary-button", closeModal));
    content.append(actions);
    openModal("Article Details", content);
}

function editArticle(id) {
    const article = articles.find(a => a.id === id); if (!article) return;
    const form = element("form", "", "article-edit-form");
    const fields = {};
    [["title", "Article title", article.title], ["author", "Author name", article.author], ["description", "Article details", article.description || ""]].forEach(([key, labelText, value]) => {
        const label = element("label", labelText); const input = key === "description" ? document.createElement("textarea") : document.createElement("input");
        input.value = value; input.required = key !== "description"; if (key === "title") input.maxLength = 160; label.append(input); form.append(label); fields[key] = input;
    });
    const catLabel = element("label", "Category"); const cat = document.createElement("select");
    ["Campus Life", "Academics", "Research", "Club & Societies", "Sports"].forEach(name => { const opt = element("option", name); opt.value = name; cat.append(opt); });
    cat.value = ["Campus Life", "Academics", "Research", "Club & Societies", "Sports"].includes(article.category) ? article.category : "Academics"; catLabel.append(cat); form.append(catLabel);
    const statusLabel = element("label", "Status"); const status = document.createElement("select");
    ["Draft", "Pending", "Published", "Rejected"].forEach(name => { const opt = element("option", name); opt.value = name; status.append(opt); }); status.value = article.status; statusLabel.append(status); form.append(statusLabel);
    const actions = element("div", "", "modal-actions"); actions.append(button("Cancel", "secondary-button", closeModal));
    actions.append(button("Save Changes", "primary-button", () => {
      if (!fields.title.value.trim() || !fields.author.value.trim()) { showToast("Please enter a title and author."); return; }
      article.title = fields.title.value.trim(); article.author = fields.author.value.trim(); article.description = fields.description.value.trim(); article.category = cat.value; article.status = status.value; article.date = article.date || new Date().toISOString().slice(0,10);
      saveData(); closeModal(); refreshAll(); showToast("Article updated successfully.");
    })); form.append(actions); openModal("Edit Article", form);
}

function removeArticle(id) {
    const article = articles.find(a => a.id === id); if (!article) return;
    const content = element("div", "", "confirm-modal"); content.append(element("p", `Remove “${article.title}” from the article list?`));
    const actions = element("div", "", "modal-actions"); actions.append(button("Cancel", "secondary-button", closeModal));
    actions.append(button("Remove Article", "danger-button", () => { articles = articles.filter(a => a.id !== id); saveData(); closeModal(); refreshAll(); showToast("Article removed."); })); content.append(actions); openModal("Remove Article", content);
}

function approveArticle(id) {
    const article = articles.find(a => a.id === id);
    if (!article || article.status !== "Pending") return;

    const content = document.createElement("div");
    content.append(element("p",
        `Are you sure you want to approve "${article.title}"?`,
        "modal-content-text"));

    const actions = element("div", "", "modal-actions");
    actions.append(button("Cancel", "secondary-button", closeModal));

    actions.append(button("Confirm Approval", "primary-button", () => {
        article.status = "Published";
        article.reason = "";
        saveData();
        closeModal();
        refreshAll();
        showToast("Article approved successfully.");
    }));

    content.append(actions);
    openModal("Approve Article", content);
}

function rejectArticle(id) {
    const article = articles.find(a => a.id === id);
    if (!article || article.status !== "Pending") return;

    const content = document.createElement("div");
    content.append(element("p",
        `Please enter a reason for rejecting "${article.title}".`,
        "modal-content-text"));

    const label = element("label", "Reason for rejection");
    const textarea = document.createElement("textarea");
    textarea.placeholder = "Enter rejection reason...";
    textarea.required = true;
    label.append(textarea);
    content.append(label);

    const actions = element("div", "", "modal-actions");
    actions.append(button("Cancel", "secondary-button", closeModal));

    actions.append(button("Confirm Rejection", "danger-button", () => {
        const reason = textarea.value.trim();

        if (!reason) {
            showToast("Please enter a rejection reason.");
            textarea.focus();
            return;
        }

        article.status = "Rejected";
        article.reason = reason;
        saveData();
        closeModal();
        refreshAll();
        showToast("Article rejected. Reason recorded.");
    }));

    content.append(actions);
    openModal("Reject Article", content);
}

function createDraft() {
    const form = element("form", "", "article-edit-form");
    const title = document.createElement("input"); title.placeholder = "Draft title"; title.required = true;
    const author = document.createElement("input"); author.placeholder = "Author name"; author.value = "Administrator"; author.required = true;
    const description = document.createElement("textarea"); description.placeholder = "Write your article notes...";
    const category = document.createElement("select"); ["Campus Life", "Academics", "Research", "Club & Societies", "Sports"].forEach(name => { const option = element("option", name); option.value = name; category.append(option); });
    [["Title", title], ["Author", author], ["Details", description], ["Category", category]].forEach(([name, field]) => { const label = element("label", name); label.append(field); form.append(label); });
    const actions = element("div", "", "modal-actions"); actions.append(button("Cancel", "secondary-button", closeModal));
    actions.append(button("Save Draft", "primary-button", () => { if (!title.value.trim() || !author.value.trim()) { showToast("Enter a title and author first."); return; } articles.unshift({ id: Date.now(), title: title.value.trim(), author: author.value.trim(), category: category.value, date: new Date().toISOString().slice(0,10), status: "Draft", description: description.value.trim(), reason: "", batch: "Not provided", department: "Not provided", email: "Not provided" }); saveData(); closeModal(); refreshAll(); navigate("articles"); $("statusFilter").value = "Draft"; renderArticles(); showToast("Draft saved. You can edit it later."); }));
    form.append(actions); openModal("Create Article Draft", form);
}
$("newDraftButton").addEventListener("click", createDraft);

/* CATEGORIES */
/* CATEGORIES */
function renderCategories() {
    const grid = document.getElementById("categoryGrid");
    if (!grid) return;
    grid.replaceChildren();

    // Names and counts use the existing article data, from the database.
    const categories = [
        { name: "Campus Life", icon: "fa-building-columns", description: "Campus events and student activities.", slug: "campus", image: "campus-life.jpg" },
        { name: "Academics", icon: "fa-graduation-cap", description: "Academic news, courses and student notices.", slug: "academics", image: "academy.jpg" },
        { name: "Research", icon: "fa-flask", description: "Research projects and new discoveries.", slug: "research", image: "research.jpg" },
        { name: "Club & Societies", icon: "fa-people-group", description: "Club events and society activities.", slug: "clubs", image: "arts-culture.jpg" },
        { name: "Sports", icon: "fa-trophy", description: "University sports and competitions.", slug: "sports", image: "sports.jpg" }
    ];

    categories.forEach((category, index) => {
        const categorySlugs = {
            campus: ["campus-life", "events", "notices"],
            academics: ["academics", "business", "computing", "engineering"],
            research: ["research"],
            clubs: ["clubs-societies", "club-societies", "clubs"],
            sports: ["sports"]
        };
        const count = articles.filter(article => {
            const slug = String(article.category_slug || article.category || "").toLowerCase().trim().replace(/&/g, "and").replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
            return (categorySlugs[category.slug] || []).includes(slug) || (category.slug === "research" && /\bresearch\b/i.test(article.title || ""));
        }).length;
        const card = document.createElement("article");
        card.className = `bento-category bento-${category.slug}`;
        card.style.setProperty("--card-index", String(index));

        const image = document.createElement("img");
        image.className = "bento-category-image";
        image.src = `assets/images/categories/${category.image}`;
        image.alt = "";
        image.loading = index === 0 ? "eager" : "lazy";
        image.decoding = "async";
        card.append(image);

        const tint = document.createElement("div");
        tint.className = "bento-category-tint";
        card.append(tint);

        const top = document.createElement("div");
        top.className = "bento-category-top";
        const icon = document.createElement("span");
        icon.className = "bento-category-icon";
        const glyph = document.createElement("i");
        glyph.className = `fa-solid ${category.icon}`;
        glyph.setAttribute("aria-hidden", "true");
        icon.append(glyph);
        const number = document.createElement("span");
        number.className = "bento-category-number";
        number.textContent = String(index + 1).padStart(2, "0");
        top.append(icon, number);

        const bottom = document.createElement("div");
        bottom.className = "bento-category-bottom";
        const heading = document.createElement("h3");
        heading.textContent = category.name;
        const desc = document.createElement("p");
        desc.textContent = category.description;
        const meta = document.createElement("div");
        meta.className = "bento-category-meta";
        const countLabel = document.createElement("span");
        countLabel.className = "bento-category-count";
        countLabel.textContent = `${count} ${count === 1 ? "article" : "articles"}`;
        const decorativeArrow = document.createElement("span");
        decorativeArrow.className = "bento-category-arrow";
        decorativeArrow.setAttribute("aria-hidden", "true");
        decorativeArrow.textContent = "↗";
        meta.append(countLabel, decorativeArrow);
        bottom.append(heading, desc, meta);
        card.append(top, bottom);
        grid.append(card);
    });
}

/* ANNOUNCEMENTS */

/* ANNOUNCEMENTS */
function renderAnnouncements() {
    const list = $("announcementList");
    list.replaceChildren();

    const search = $("announcementSearch").value.trim().toLowerCase();
    const status = $("announcementFilter").value;

    const results = announcements.filter(item => {
        const matchesText =
            item.title.toLowerCase().includes(search) ||
            item.message.toLowerCase().includes(search);

        return matchesText && (status === "all" || item.status === status);
    });

    if (results.length === 0) {
        list.append(element("p", "No announcements found."));
        return;
    }

    results.forEach(item => {
        const card = element("article", "", "announcement-card");

        const icon = element("div", "", "announcement-icon");
        icon.innerHTML = '<i class="fa-solid fa-bullhorn"></i>';

        const details = document.createElement("div");
        details.append(element("h3", item.title));
        details.append(element("p", item.message));
        details.append(element("small", `${item.date} · ${item.status}`));

        const actions = element("div", "", "announcement-actions");
        actions.append(button("View", "small-button", () => {
            const content = document.createElement("div");
            content.append(element("h3", item.title));
            content.append(element("p", item.message, "modal-content-text"));
            content.append(element("p", `Date: ${item.date}`));
            const modalActions = element("div", "", "modal-actions");
            modalActions.append(button("Edit", "primary-button", () => editAnnouncement(item.id)));
            modalActions.append(button("Remove", "danger-button", () => removeAnnouncement(item.id)));
            modalActions.append(button("Close", "secondary-button", closeModal));
            content.append(modalActions);
            openModal("Announcement Details", content);
        }));
        actions.append(button("Edit", "small-button edit-action", () => editAnnouncement(item.id)));
        actions.append(button("Remove", "small-button remove-action", () => removeAnnouncement(item.id)));

        card.append(icon, details, actions);
        list.append(card);
    });
}

function editAnnouncement(id) {
    const item = announcements.find(a => a.id === id); if (!item) return;
    const content = element("form", "", "article-edit-form");
    const title = document.createElement("input"); title.value = item.title; title.required = true;
    const message = document.createElement("textarea"); message.value = item.message; message.required = true;
    const status = document.createElement("select"); ["Published", "Draft", "Pending"].forEach(value => { const option = element("option", value); option.value = value; status.append(option); }); status.value = item.status;
    [["Title", title], ["Message", message], ["Status", status]].forEach(([labelText, field]) => { const label = element("label", labelText); label.append(field); content.append(label); });
    const actions = element("div", "", "modal-actions"); actions.append(button("Cancel", "secondary-button", closeModal));
    actions.append(button("Save Changes", "primary-button", () => { if (!title.value.trim() || !message.value.trim()) { showToast("Fill in the title and message."); return; } item.title = title.value.trim(); item.message = message.value.trim(); item.status = status.value; saveData(); closeModal(); renderAnnouncements(); showToast("Announcement updated."); })); content.append(actions); openModal("Edit Announcement", content);
}
function removeAnnouncement(id) {
    const item = announcements.find(a => a.id === id); if (!item) return;
    const content = element("div", "", "confirm-modal"); content.append(element("p", `Remove “${item.title}”?`));
    const actions = element("div", "", "modal-actions"); actions.append(button("Cancel", "secondary-button", closeModal));
    actions.append(button("Remove", "danger-button", () => { announcements = announcements.filter(a => a.id !== id); saveData(); closeModal(); renderAnnouncements(); showToast("Announcement removed."); })); content.append(actions); openModal("Remove Announcement", content);
}

function createAnnouncement() {
    const form = document.createElement("form");

    const titleLabel = element("label", "Announcement title");
    const titleInput = document.createElement("input");
    titleInput.required = true;
    titleInput.maxLength = 120;
    titleInput.placeholder = "Enter announcement title";
    titleLabel.append(titleInput);

    const messageLabel = element("label", "Announcement message");
    const messageInput = document.createElement("textarea");
    messageInput.required = true;
    messageInput.maxLength = 1000;
    messageInput.placeholder = "Write your announcement...";
    messageLabel.append(messageInput);

    const statusLabel = element("label", "Status"); const statusInput = document.createElement("select");
    ["Published", "Draft", "Pending"].forEach(value => { const option = element("option", value); option.value = value; statusInput.append(option); }); statusLabel.append(statusInput);
    const actions = element("div", "", "modal-actions");
    actions.append(button("Cancel", "secondary-button", closeModal));

    const publish = button("Save Announcement", "primary-button", () => {
        const title = titleInput.value.trim();
        const message = messageInput.value.trim();

        if (!title || !message) {
            showToast("Please fill in both fields.");
            return;
        }

        announcements.unshift({
            id: Date.now(),
            title,
            message,
            date: new Date().toLocaleDateString(),
            status: statusInput.value
        });
        saveData();

        closeModal();
        renderAnnouncements();
        showToast("Announcement published.");
    });

    actions.append(publish);
    form.append(titleLabel, messageLabel, actions);

    form.addEventListener("submit", event => event.preventDefault());

    openModal("New Announcement", form);
}

$("newAnnouncement").addEventListener("click", createAnnouncement);
$("quickAnnouncement").addEventListener("click", () => {
    navigate("announcements");
    createAnnouncement();
});

$("announcementSearch").addEventListener("input", renderAnnouncements);
$("announcementFilter").addEventListener("change", renderAnnouncements);

/* REPORTS AND CHARTS */
function renderReports() {
    const counts = {
        Pending: articles.filter(a => a.status === "Pending").length,
        Published: articles.filter(a => a.status === "Published").length,
        Rejected: articles.filter(a => a.status === "Rejected").length,
        Draft: articles.filter(a => a.status === "Draft").length
    };

    $("reportTotal").textContent = articles.length;
    $("reportPending").textContent = counts.Pending;
    $("reportPublished").textContent = counts.Published;
    $("reportRejected").textContent = counts.Rejected;
    if ($("reportDraft")) $("reportDraft").textContent = counts.Draft;

    const total = articles.length;
    const rate = total ? Math.round(counts.Published / total * 100) : 0;
    $("reportProgressFill").style.width = `${rate}%`;
    $("reportPublishRate").textContent = `${rate}% published`;
    $("reportRemaining").textContent = `${total - counts.Published} awaiting publication`;
    $("reportSpotlight").textContent = counts.Pending ? `${counts.Pending} articles need review` : "All reviews up to date";
    $("reportSpotlightDetail").textContent = counts.Pending ? "Open Manage Articles to approve or reject pending submissions." : "No pending submissions at the moment.";

    const insights = $("insightItems");
    if (insights) {
        insights.replaceChildren();
        const covered = new Set(articles.map(a => a.category)).size;
        const rows = [
          ["fa-clock", `${counts.Pending} awaiting review`, counts.Pending ? "Open Manage Articles to review pending submissions." : "No submissions currently require review."],
          ["fa-circle-check", `${rate}% publication rate`, `${counts.Published} published out of ${total} total articles.`],
          ["fa-layer-group", `${covered} active categories`, "Category coverage is calculated from current articles."]
        ];
        rows.forEach(([icon,title,detail],index) => {
          const item=document.createElement("div");item.className="insight-item";
          const ic=document.createElement("span");ic.className="insight-symbol";const i=document.createElement("i");i.className=`fa-solid ${icon}`;ic.append(i);
          const copy=document.createElement("div");const heading=document.createElement("strong");heading.textContent=title;const sub=document.createElement("p");sub.textContent=detail;copy.append(heading,sub);item.append(ic,copy);
          if(index===0){item.classList.add("insight-action");item.tabIndex=0;item.setAttribute("role","button");item.setAttribute("aria-label","Open Manage Articles");item.addEventListener("click",()=>navigate("articles"));item.addEventListener("keydown",e=>{if(e.key==="Enter"||e.key===" "){e.preventDefault();navigate("articles");}});}
          insights.append(item);
        });
    }
    if (typeof Chart === "undefined") {
        showToast("Charts need an internet connection to load Chart.js.");
        return;
    }

    if (statusChart) statusChart.destroy();
    if (categoryChart) categoryChart.destroy();

    const dark = document.documentElement.dataset.theme === "dark";
    const chartText = dark ? "#b6cfc2" : "#667f73";
    const chartGrid = dark ? "rgba(185,232,205,.10)" : "rgba(11,91,62,.09)";
    statusChart = new Chart($("statusChart"), {
        type: "doughnut",
        data: {
            labels: ["Pending", "Published", "Rejected", "Draft"],
            datasets: [{
                data: [counts.Pending, counts.Published, counts.Rejected, counts.Draft],
                backgroundColor: ["#f4b942", "#07805d", "#e34b58", "#7c6ce7"],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: "bottom", labels: { color: chartText, usePointStyle: true, padding: 18 } }
            }
        }
    });

    const categories = {};

    articles.forEach(article => {
        categories[article.category] = (categories[article.category] || 0) + 1;
    });

    categoryChart = new Chart($("categoryChart"), {
        type: "bar",
        data: {
            labels: Object.keys(categories),
            datasets: [{
                label: "Articles",
                data: Object.values(categories),
                backgroundColor: dark ? "#54d7a1" : "#087857",
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0, color: chartText }, grid: { color: chartGrid } },
                y: { ticks: { color: chartText }, grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

/* SEARCH */
$("articleSearch").addEventListener("input", renderArticles);
$("statusFilter").addEventListener("change", renderArticles);

const globalSearch = $("globalSearch") || $("searchInput");
if (globalSearch) globalSearch.addEventListener("input", () => {
    $("articleSearch").value = globalSearch.value;
    navigate("articles");
    renderArticles();
});
const headerSearch = $("headerSearch");
if (headerSearch) headerSearch.addEventListener("submit", event => event.preventDefault());

const profileButton = $("profileButton");

if (profileButton) {
    profileButton.addEventListener("click", () => {
        showToast("Administrator profile.");
    });
}
/* DOWNLOAD REPORT */
$("downloadReport").addEventListener("click", () => {
    const rows = [
        ["Title", "Author", "Category", "Date", "Status"],
        ...articles.map(a => [a.title, a.author, a.category, a.date, a.status])
    ];

    const csv = rows.map(row =>
        row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(",")
    ).join("\r\n");

    const blob = new Blob(["\uFEFF" + csv], {
        type: "text/csv;charset=utf-8;"
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "NSBM-NewsDesk-Report.csv";
    link.click();
    URL.revokeObjectURL(url);

    showToast("Report downloaded.");
});

/* MODAL CONTROLS */
/* MODAL CONTROLS */

// Close button (X)
const closeButton = $("closeModal");

if (closeButton) {
    closeButton.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        closeModal();
    });
}

// Click outside the modal to close it
$("modalBackdrop").addEventListener("click", function (event) {
    if (event.target === $("modalBackdrop")) {
        closeModal();
    }
});

// Press Escape to close the modal
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

/* UPDATE ALL VIEWS */
function refreshAll() {
    renderDashboard();
    renderArticles();
    renderCategories();

    if (!$("announcementsPage").classList.contains("hidden")) {
        renderAnnouncements();
    }

    if (!$("commentsPage").classList.contains("hidden")) renderComments();

    if (!$("reportsPage").classList.contains("hidden")) {
        renderReports();
    }
}

/* START */
renderDashboard();
renderArticles();
renderCategories();
renderAnnouncements();

const startingPage = location.hash.replace("#", "") || "dashboard";
navigate(startingPage);

/* Comments are loaded by the database integration layer. */
let commentTab = "pending";
let demoComments = [];
function renderComments() {
    const counts = {pending:0, approved:0, removed:0};
    demoComments.forEach(comment => { if (Object.hasOwn(counts, comment.status)) counts[comment.status]++; });
    $("commentPendingCount").textContent = counts.pending;
    $("approvedCount").textContent = counts.approved;
    $("removedCount").textContent = counts.removed;
    const list = $("commentsList"); list.replaceChildren();
    const items = demoComments.filter(comment => comment.status === commentTab);
    if (!items.length) { list.append(element("p", "No " + commentTab + " comments found.", "empty-message")); return; }
    items.forEach(comment => {
        const card = element("article", "", "content-card");
        card.append(element("strong", comment.author || "Student"), element("p", comment.text || ""));
        const actions = element("div", "", "action-group");
        const change = (status) => { comment.status = status; localStorage.setItem("nsbmNewsDeskComments", JSON.stringify(demoComments)); renderComments(); showToast("Comment updated."); };
        if (commentTab === "pending") actions.append(button("Approve", "small-button approve", () => change("approved")));
        if (commentTab !== "removed") actions.append(button("Remove", "small-button reject", () => change("removed")));
        card.append(actions); list.append(card);
    });
}
function showComments(tab, selectedButton) {
    commentTab = tab;
    document.querySelectorAll(".comment-tabs button").forEach(b => b.classList.toggle("active", b === selectedButton));
    renderComments();
}
function showHeaderMessage(label) { showToast(label); }

/* Responsive navigation */
const sidebarToggle = document.getElementById("sidebarToggle");
const adminSidebar = document.getElementById("adminSidebar");
if (sidebarToggle && adminSidebar) {
  sidebarToggle.addEventListener("click", () => {
    const open = adminSidebar.classList.toggle("mobile-open");
    sidebarToggle.setAttribute("aria-expanded", String(open));
  });
  document.querySelectorAll(".side-link").forEach(link => link.addEventListener("click", () => {
    adminSidebar.classList.remove("mobile-open");
    sidebarToggle.setAttribute("aria-expanded", "false");
  }));
}
