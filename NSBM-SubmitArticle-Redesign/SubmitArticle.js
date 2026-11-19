"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const app = window.NSBM;
    const articleForm = document.getElementById("articleForm");
    const titleInput = document.getElementById("articleTitle");
    const categoryInput = document.getElementById("category");
    const authorInput = document.getElementById("author");
    const summaryInput = document.getElementById("summary");
    const contentInput = document.getElementById("content");
    const summaryCount = document.getElementById("summaryCount");
    const writeTab = document.getElementById("writeTab");
    const previewTab = document.getElementById("previewTab");
    const contentPreview = document.getElementById("contentPreview");
    const coverImage = document.getElementById("coverImage");
    const articleContentImage = document.getElementById("articleContentImage");
    const uploadArea = document.getElementById("uploadArea");
    const imagePreview = document.getElementById("imagePreview");
    const articleContentImagePreview = document.getElementById("articleContentImagePreview");
    const saveDraftButton = document.getElementById("saveDraft");
    const submitButton = document.getElementById("submitArticle");
    const formMessage = document.getElementById("formMessage");
    const pendingCount = document.getElementById("pendingCount");
    const publishedCount = document.getElementById("publishedCount");
    const totalCount = document.getElementById("totalCount");
    const publicationRate = document.getElementById("publicationRate");
    const publicationBar = document.getElementById("publicationBar");
    const publicationHint = document.getElementById("publicationHint");
    const completionPercent = document.getElementById("completionPercent");
    const completionBar = document.getElementById("completionBar");
    let selectedImageURL = null;
    let selectedContentImageURL = null;
    let editingArticleId = 0;

    if (!app || !articleForm) return;

    function showMessage(message, type) {
        formMessage.textContent = message;
        formMessage.className = "form-message " + type;
    }

    function updateCompleteness() {
        const fields = [titleInput, categoryInput, summaryInput, contentInput];
        const completed = fields.filter(field => field && field.value.trim().length > 0).length;
        const percent = Math.round(completed / fields.length * 100);
        completionPercent.textContent = percent + "%";
        completionBar.style.width = percent + "%";
    }

    function updateSummaryCount() {
        summaryCount.textContent = summaryInput.value.length + "/300";
    }

    function showWriteTab() {
        writeTab.classList.add("active");
        previewTab.classList.remove("active");
        contentInput.hidden = false;
        contentPreview.hidden = true;
    }

    function displayImage(file) {
        if (!file) return;
        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            showMessage("Please select a JPG, PNG or WEBP image.", "error");
            coverImage.value = "";
            return;
        }
        if (file.size > 4 * 1024 * 1024) {
            showMessage("Image size must be no larger than 4 MB.", "error");
            coverImage.value = "";
            return;
        }
        if (selectedImageURL) URL.revokeObjectURL(selectedImageURL);
        selectedImageURL = URL.createObjectURL(file);
        imagePreview.replaceChildren();
        const image = document.createElement("img");
        image.src = selectedImageURL;
        image.alt = "Selected cover image";
        image.style.maxWidth = "100%";
        image.style.maxHeight = "220px";
        image.style.objectFit = "cover";
        image.style.borderRadius = "10px";
        imagePreview.appendChild(image);
        showMessage("Image selected. It will be uploaded with your submission.", "info");
    }

    function displayContentImage(file) {
        if (!file) return;
        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
            showMessage("Please select a JPG, PNG or WEBP article content image.", "error");
            articleContentImage.value = "";
            return;
        }
        if (file.size > 4 * 1024 * 1024) {
            showMessage("Article content image must be no larger than 4 MB.", "error");
            articleContentImage.value = "";
            return;
        }
        if (selectedContentImageURL) URL.revokeObjectURL(selectedContentImageURL);
        selectedContentImageURL = URL.createObjectURL(file);
        articleContentImagePreview.replaceChildren();
        const image = document.createElement("img");
        image.src = selectedContentImageURL;
        image.alt = "Selected article content image";
        articleContentImagePreview.appendChild(image);
        showMessage("Article content image selected. It will appear inside your published article.", "info");
    }

    function showStoredImage(container, path, alt) {
        container.replaceChildren();
        if (typeof path !== "string" || !/^(?:uploads\/(?:covers|article-content)\/[a-f0-9]{24,32}\.(?:jpg|png|webp)|news\/assets\/images\/[a-zA-Z0-9%._/-]+|home\/(?:assets\/)?images\/[a-zA-Z0-9%._/-]+)$/.test(path)) return;
        const image = document.createElement("img");
        image.src = app.route(path);
        image.alt = alt;
        image.style.maxWidth = "100%";
        image.style.maxHeight = "220px";
        image.style.objectFit = "cover";
        image.style.borderRadius = "10px";
        container.appendChild(image);
    }

    async function loadWorkspace() {
        try {
            const results = await Promise.all([app.api("categories"), app.api("profile"), app.api("articles")]);
            
const allowedCategories = [
    "Campus Life",
    "Academics",
    "Research",
    "Clubs & Societies",
    "Sports"
];

const categories = results[0].categories.filter(category =>
    Number(category.is_active) !== 0 &&
    allowedCategories.includes(category.name.trim())
);

categoryInput.replaceChildren(new Option("Select a category", ""));

categories.forEach(category =>
    categoryInput.add(new Option(category.name, String(category.id)))
);

            authorInput.value = results[1].profile?.name || "";
            authorInput.readOnly = true;

            const articles = results[2].articles || [];
            const requestedEditId = new URLSearchParams(window.location.search).get("edit");
            if (requestedEditId) {
                const article = articles.find(item => String(item.id) === requestedEditId);
                if (!article || article.status === "archived") {
                    showMessage("This submission is unavailable for editing.", "error");
                    submitButton.disabled = true;
                    saveDraftButton.disabled = true;
                } else {
                    editingArticleId = Number(article.id);
                    titleInput.value = article.title || "";
                    categoryInput.value = String(article.category_id || "");
                    summaryInput.value = article.summary || "";
                    contentInput.value = article.body || "";
                    showStoredImage(imagePreview, article.cover_image_url, "Current cover image");
                    showStoredImage(articleContentImagePreview, article.content_image_url, "Current article content image");
                    document.querySelector(".section-heading h2").textContent = "Edit your article";
                    submitButton.firstChild.textContent = "Update and Resubmit";
                    saveDraftButton.hidden = true;
                    showMessage(article.status === "published"
                        ? "Editing this published story will send it back for review."
                        : "Update your article and submit it for review.", "info");
                }
            }
            const pending = articles.filter(article => article.status === "pending_review").length;
            const published = articles.filter(article => article.status === "published").length;
            const total = articles.length;
            const rate = total ? Math.round(published / total * 100) : 0;
            pendingCount.textContent = String(pending).padStart(2, "0");
            publishedCount.textContent = String(published).padStart(2, "0");
            totalCount.textContent = String(total).padStart(2, "0");
            publicationRate.textContent = rate + "%";
            publicationBar.style.width = rate + "%";
            publicationHint.textContent = total
                ? published + " of " + total + " submissions published"
                : "Your publishing journey starts here.";
        } catch (error) {
            showMessage("Could not load your account details: " + error.message, "error");
            app.toast(error.message, false);
        }
        updateCompleteness();
    }

    async function saveArticle(status) {
        const isDraft = status === "draft";
        if (!isDraft && !articleForm.reportValidity()) return;
        const title = titleInput.value.trim();
        const body = contentInput.value.trim();
        if (title.length < 4 || body.length < 10) {
            showMessage("Enter a title of at least 4 characters and article content of at least 10 characters.", "error");
            return;
        }
        if (!categoryInput.value) {
            showMessage("Select an article category.", "error");
            return;
        }

        saveDraftButton.disabled = true;
        submitButton.disabled = true;
        showMessage(isDraft ? "Saving your draft..." : "Submitting your article for review...", "info");

        try {
            let coverImageUrl = "";
            if (coverImage.files[0]) {
                const data = new FormData();
                data.append("image", coverImage.files[0]);
                coverImageUrl = (await app.api("cover", "POST", data)).url;
            }
            let contentImageUrl = "";
            if (articleContentImage.files[0]) {
                const data = new FormData();
                data.append("image", articleContentImage.files[0]);
                contentImageUrl = (await app.api("article-content-image", "POST", data)).url;
            }
            const payload = {
                title: title,
                category: Number(categoryInput.value),
                summary: summaryInput.value.trim(),
                body: body,
                status: editingArticleId ? "pending_review" : status
            };
            if (editingArticleId) payload.id = editingArticleId;
            if (coverImage.files[0]) payload.cover_image_url = coverImageUrl;
            if (articleContentImage.files[0]) payload.content_image_url = contentImageUrl;
            await app.api("articles", "POST", payload);
            showMessage(editingArticleId ? "Article updated and submitted for review." : isDraft ? "Draft saved successfully." : "Article submitted successfully for review.", "success");
            app.toast(editingArticleId ? "Article updated and sent for review." : isDraft ? "Draft saved." : "Article submitted for review.");
            if (editingArticleId) {
                window.location.href = app.route("my-submissions.php?updated=1");
                return;
            }
            await refreshCounts();
            if (!isDraft) {
                articleForm.reset();
                imagePreview.replaceChildren();
                articleContentImagePreview.replaceChildren();
                if (selectedImageURL) URL.revokeObjectURL(selectedImageURL);
                if (selectedContentImageURL) URL.revokeObjectURL(selectedContentImageURL);
                selectedImageURL = null;
                selectedContentImageURL = null;
                updateSummaryCount();
                showWriteTab();
            }
            updateCompleteness();
        } catch (error) {
            showMessage(error.message || "Unable to save your article.", "error");
            app.toast(error.message || "Unable to save your article.", false);
        } finally {
            saveDraftButton.disabled = false;
            submitButton.disabled = false;
        }
    }

    async function refreshCounts() {
        const response = await app.api("articles");
        const articles = response.articles || [];
        const pending = articles.filter(article => article.status === "pending_review").length;
        const published = articles.filter(article => article.status === "published").length;
        const total = articles.length;
        const rate = total ? Math.round(published / total * 100) : 0;
        pendingCount.textContent = String(pending).padStart(2, "0");
        publishedCount.textContent = String(published).padStart(2, "0");
        totalCount.textContent = String(total).padStart(2, "0");
        publicationRate.textContent = rate + "%";
        publicationBar.style.width = rate + "%";
        publicationHint.textContent = total
            ? published + " of " + total + " submissions published"
            : "Your publishing journey starts here.";
    }

    [titleInput, categoryInput, authorInput, summaryInput, contentInput].forEach(field => {
        field.addEventListener("input", updateCompleteness);
        field.addEventListener("change", updateCompleteness);
    });
    summaryInput.addEventListener("input", updateSummaryCount);
    writeTab.addEventListener("click", showWriteTab);
    previewTab.addEventListener("click", function () {
        writeTab.classList.remove("active");
        previewTab.classList.add("active");
        contentPreview.textContent = contentInput.value.trim() || "Nothing to preview yet.";
        contentInput.hidden = true;
        contentPreview.hidden = false;
    });
    coverImage.addEventListener("change", () => displayImage(coverImage.files[0]));
    articleContentImage.addEventListener("change", () => displayContentImage(articleContentImage.files[0]));
    uploadArea.addEventListener("dragover", event => {
        event.preventDefault();
        uploadArea.classList.add("drag-over");
    });
    uploadArea.addEventListener("dragleave", () => uploadArea.classList.remove("drag-over"));
    uploadArea.addEventListener("drop", event => {
        event.preventDefault();
        uploadArea.classList.remove("drag-over");
        const file = event.dataTransfer.files[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        coverImage.files = transfer.files;
        displayImage(file);
    });
    saveDraftButton.addEventListener("click", () => saveArticle("draft"));
    articleForm.addEventListener("submit", function (event) {
        event.preventDefault();
        saveArticle("pending_review");
    });
    document.querySelectorAll("[data-status-link]").forEach(link => {
        link.href = app.route("my-submissions.php");
    });

    updateSummaryCount();
    loadWorkspace();
});
