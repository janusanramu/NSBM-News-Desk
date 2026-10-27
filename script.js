const articles = [
    {
        id: 1,
        title: "AI & Robotics Exhibition 2026",
        author: "Tharindu Silva",
        category: "Technology",
        date: "2026-08-24",
        status: "Pending",
        description: "A student exhibition showcasing artificial intelligence and robotics projects.",
        reason: ""
    },
    {
        id: 2,
        title: "Inter-University Cricket Championship",
        author: "Kavindu Perera",
        category: "Sports",
        date: "2026-08-23",
        status: "Pending",
        description: "Information about the inter-university cricket championship.",
        reason: ""
    },
    {
        id: 3,
        title: "Annual Gavel Club Poetry Slam",
        author: "Anjal Mendis",
        category: "Arts & Culture",
        date: "2026-08-22",
        status: "Pending",
        description: "A poetry event organised for the campus community.",
        reason: ""
    },
    {
        id: 4,
        title: "Green University Tree Planting",
        author: "Sahan Jayasinghe",
        category: "Campus Life",
        date: "2026-08-21",
        status: "Pending",
        description: "A campus tree-planting and environmental awareness initiative.",
        reason: ""
    },
    {
        id: 5,
        title: "Library Extended Hours",
        author: "Student Affairs",
        category: "Notice",
        date: "2026-08-20",
        status: "Published",
        description: "The library will remain open longer during the examination period.",
        reason: ""
    }
];

let announcements = [
    {
        id: 1,
        title: "Library Extended Hours",
        message: "The library will remain open longer during the examination period.",
        date: "2026-08-20",
        status: "Published"
    },
    {
        id: 2,
        title: "Career Fair 2026",
        message: "Students are invited to attend the university career fair.",
        date: "2026-08-18",
        status: "Published"
    },
    {
        id: 3,
        title: "Sports Festival Registration",
        message: "Registration is open for the upcoming campus sports festival.",
        date: "2026-08-15",
        status: "Published"
    }
];

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
        "announcements", "reports"
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
function createActions(article) {
    const group = element("div", "", "action-group");

    group.append(button("View", "small-button", () => viewArticle(article.id)));

    if (article.status === "Pending") {
        group.append(button("Approve", "small-button approve",
            () => approveArticle(article.id)));

        group.append(button("Reject", "small-button reject",
            () => rejectArticle(article.id)));
    }

    return group;
}

function renderDashboard() {
    const pending = articles.filter(a => a.status === "Pending");

    $("totalArticles").textContent = articles.length;
    $("pendingCount").textContent = pending.length;
    $("pendingLabel").textContent = `${pending.length} Pending`;

    const tbody = $("pendingTable");
    tbody.replaceChildren();

    if (pending.length === 0) {
        const row = element("tr");
        const cell = element("td", "No pending submissions.");
        cell.colSpan = 5;
        row.append(cell);
        tbody.append(row);
        return;
    }

    pending.forEach(article => {
        const row = document.createElement("tr");

        row.append(element("td", article.title));
        row.append(element("td", article.author));

        const category = document.createElement("td");
        category.append(badge(article.category));
        row.append(category);

        row.append(element("td", article.date));

        const actions = document.createElement("td");
        actions.append(createActions(article));
        row.append(actions);

        tbody.append(row);
    });
}

function renderArticles() {
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
        row.append(element("td", article.author));

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

    content.append(button("Close", "secondary-button", closeModal));
    openModal("Article Details", content);
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
        closeModal();
        refreshAll();
        showToast("Article rejected. Reason recorded in this demo.");
    }));

    content.append(actions);
    openModal("Reject Article", content);
}

/* CATEGORIES */
/* CATEGORIES */
function renderCategories() {
    const grid = document.getElementById("categoryGrid");

    if (!grid) {
        console.error("categoryGrid was not found in HTML!");
        return;
    }

    grid.innerHTML = "";

    const categoryInfo = [
        {
            name: "Technology",
            className: "category-technology",
            icon: "fa-solid fa-laptop",
            description: "Technology news and innovations."
        },
        {
            name: "Campus Life",
            className: "category-campus",
            icon: "fa-solid fa-building-columns",
            description: "Campus events and student activities."
        },
        {
            name: "Sports",
            className: "category-sports",
            icon: "fa-solid fa-trophy",
            description: "University sports and competitions."
        },
        {
            name: "Arts & Culture",
            className: "category-arts",
            icon: "fa-solid fa-palette",
            description: "Arts, culture and creative events."
        }
    ];

    categoryInfo.forEach(category => {
        const count = articles.filter(
            article => article.category === category.name
        ).length;

        const card = document.createElement("div");
        card.className = `category-card ${category.className}`;

        const icon = document.createElement("i");
        icon.className = category.icon;

        const heading = document.createElement("h3");
        heading.textContent = category.name;

        const description = document.createElement("p");
        description.textContent = category.description;

        const articleCount = document.createElement("p");
        articleCount.innerHTML = `<strong>${count}</strong> articles`;

        card.append(icon, heading, description, articleCount);
        grid.appendChild(card);
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
            content.append(button("Close", "secondary-button", closeModal));
            openModal("Announcement Details", content);
        }));

        card.append(icon, details, actions);
        list.append(card);
    });
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

    const actions = element("div", "", "modal-actions");
    actions.append(button("Cancel", "secondary-button", closeModal));

    const publish = button("Publish Announcement", "primary-button", () => {
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
            status: "Published"
        });

        closeModal();
        renderAnnouncements();
        showToast("Announcement published in this demo.");
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
        Rejected: articles.filter(a => a.status === "Rejected").length
    };

    $("reportTotal").textContent = articles.length;
    $("reportPending").textContent = counts.Pending;
    $("reportPublished").textContent = counts.Published;
    $("reportRejected").textContent = counts.Rejected;

    if (typeof Chart === "undefined") {
        showToast("Charts need an internet connection to load Chart.js.");
        return;
    }

    if (statusChart) statusChart.destroy();
    if (categoryChart) categoryChart.destroy();

    statusChart = new Chart($("statusChart"), {
        type: "doughnut",
        data: {
            labels: ["Pending", "Published", "Rejected"],
            datasets: [{
                data: [counts.Pending, counts.Published, counts.Rejected],
                backgroundColor: ["#f4b942", "#07805d", "#e34b58"],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: "bottom" }
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
                backgroundColor: "#087857",
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
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

$("globalSearch").addEventListener("input", () => {
    $("articleSearch").value = $("globalSearch").value;
    navigate("articles");
    renderArticles();
});

$("profileButton").addEventListener("click", () => {
    showToast("Administrator profile demo.");
});

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
$("closeModal").addEventListener("click", closeModal);

$("modalBackdrop").addEventListener("click", event => {
    if (event.target === $("modalBackdrop")) closeModal();
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") closeModal();
});

/* UPDATE ALL VIEWS */
function refreshAll() {
    renderDashboard();
    renderArticles();
    renderCategories();

    if (!$("announcementsPage").classList.contains("hidden")) {
        renderAnnouncements();
    }

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