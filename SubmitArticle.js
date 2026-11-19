
"use strict";

document.addEventListener("DOMContentLoaded", function () {

    // Get HTML elements
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
    const uploadArea = document.getElementById("uploadArea");
    const imagePreview = document.getElementById("imagePreview");

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

    // Live article completeness indicator (cover image is optional)
    function updateCompleteness() {
        const fields = [titleInput, categoryInput, authorInput, summaryInput, contentInput];
        const completed = fields.filter(field => field && field.value.trim().length > 0).length;
        const percent = Math.round(completed / fields.length * 100);
        if (completionPercent) completionPercent.textContent = percent + "%";
        if (completionBar) completionBar.style.width = percent + "%";
    }
    [titleInput, categoryInput, authorInput, summaryInput, contentInput].forEach(field => {
        if (field) { field.addEventListener("input", updateCompleteness); field.addEventListener("change", updateCompleteness); }
    });
    updateCompleteness();

    // Display message
    function showMessage(message, type) {
        if (!formMessage) return;

        formMessage.textContent = message;
        formMessage.className = "form-message " + type;
    }

    // Update summary character count
    function updateSummaryCount() {
        if (!summaryInput || !summaryCount) return;

        summaryCount.textContent =
            summaryInput.value.length + "/300";
    }

    if (summaryInput) {
        summaryInput.addEventListener("input", updateSummaryCount);
        updateSummaryCount();
    }

    // Write and Preview tabs
    function showWriteTab() {
        if (!writeTab || !previewTab) return;

        writeTab.classList.add("active");
        previewTab.classList.remove("active");

        contentInput.hidden = false;
        contentPreview.hidden = true;
    }

    function showPreviewTab() {
        if (!writeTab || !previewTab) return;

        writeTab.classList.remove("active");
        previewTab.classList.add("active");

        const content = contentInput.value.trim();

        contentPreview.textContent = content || "Nothing to preview yet.";

        contentInput.hidden = true;
        contentPreview.hidden = false;
    }

    if (writeTab) {
        writeTab.addEventListener("click", showWriteTab);
    }

    if (previewTab) {
        previewTab.addEventListener("click", showPreviewTab);
    }

    // Image preview
    function displayImage(file) {

        if (!file) return;

        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];

        if (!allowedTypes.includes(file.type)) {
            showMessage(
                "Please select a JPG, PNG or WEBP image.",
                "error"
            );

            coverImage.value = "";
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showMessage(
                "Image size must be less than 5MB.",
                "error"
            );

            coverImage.value = "";
            return;
        }

        if (selectedImageURL) {
            URL.revokeObjectURL(selectedImageURL);
        }

        selectedImageURL = URL.createObjectURL(file);

        if (imagePreview) {
            imagePreview.innerHTML = "";

            const image = document.createElement("img");

            image.src = selectedImageURL;
            image.alt = "Selected cover image";

            image.style.maxWidth = "100%";
            image.style.maxHeight = "220px";
            image.style.objectFit = "cover";
            image.style.borderRadius = "10px";

            imagePreview.appendChild(image);
        }

        showMessage("Image selected successfully.", "success");
    }

    if (coverImage) {
        coverImage.addEventListener("change", function () {
            const file = this.files[0];

            if (file) {
                displayImage(file);
            }
        });
    }

    // Drag and drop image upload
    if (uploadArea && coverImage) {

        uploadArea.addEventListener("dragover", function (event) {
            event.preventDefault();
            uploadArea.classList.add("drag-over");
        });

        uploadArea.addEventListener("dragleave", function () {
            uploadArea.classList.remove("drag-over");
        });

        uploadArea.addEventListener("drop", function (event) {
            event.preventDefault();

            uploadArea.classList.remove("drag-over");

            const file = event.dataTransfer.files[0];

            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);

                coverImage.files = dataTransfer.files;

                displayImage(file);
            }
        });
    }

    // Load article counts
    async function loadArticleCounts() {

        try {
            const response = await fetch(
                "api/get_article_counts.php",
                { cache: "no-store" }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error("Unable to load article counts.");
            }

            if (pendingCount) {
                pendingCount.textContent = String(data.pending).padStart(2, "0");
            }

            if (publishedCount) {
                publishedCount.textContent = String(data.published).padStart(2, "0");
            }
            const pending = Math.max(0, Number(data.pending) || 0);
            const published = Math.max(0, Number(data.published) || 0);
            const total = pending + published;
            const rate = total ? Math.round(published / total * 100) : 0;
            if (totalCount) totalCount.textContent = String(total).padStart(2, "0");
            if (publicationRate) publicationRate.textContent = rate + "%";
            if (publicationBar) publicationBar.style.width = rate + "%";
            if (publicationHint) publicationHint.textContent = total ? `${published} of ${total} reviewed/pending articles published` : "Your publishing journey starts here.";

        } catch (error) {
            console.error("Count loading error:", error);
        }
    }

    // Send article data to PHP and database
    async function saveArticle(status) {

        const isDraft = status === "Draft";

        // Validate required fields only for submission
        if (!isDraft && !articleForm.reportValidity()) {
            return;
        }

        const formData = new FormData(articleForm);

        // Send status to PHP
        formData.set("status", status);

        const activeButton = isDraft
            ? saveDraftButton
            : submitButton;

        if (activeButton) {
            activeButton.disabled = true;
        }

        if (saveDraftButton) {
            saveDraftButton.disabled = true;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        showMessage(
            isDraft ? "Saving draft..." : "Submitting article...",
            "info"
        );

        try {

            const response = await fetch("api/submit_article.php", {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || "Something went wrong."
                );
            }

            if (isDraft) {

                showMessage(
                    "Draft saved successfully!",
                    "success"
                );

                // Keep the form details so the user can continue editing

            } else {

                showMessage(
                    "Article submitted successfully for review!",
                    "success"
                );

                articleForm.reset();
                updateCompleteness();

                updateSummaryCount();
                showWriteTab();

                if (imagePreview) {
                    imagePreview.innerHTML = "";
                }

                if (selectedImageURL) {
                    URL.revokeObjectURL(selectedImageURL);
                    selectedImageURL = null;
                }
            }

            // Refresh Pending Review and Published counts
            await loadArticleCounts();

        } catch (error) {

            console.error("Article saving error:", error);

            showMessage(
                error.message || "Unable to save article.",
                "error"
            );

        } finally {

            if (saveDraftButton) {
                saveDraftButton.disabled = false;
            }

            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    // Save Draft button
    if (saveDraftButton) {
        saveDraftButton.addEventListener("click", function () {
            saveArticle("Draft");
        });
    }

    // Submit for Review
    if (articleForm) {

        articleForm.addEventListener("submit", function (event) {

            event.preventDefault();

            saveArticle("Pending Review");

        });
    }

    // Status tiles can be linked to a real "My Articles" page once that route exists.
    document.querySelectorAll("[data-status-link]").forEach(link => {
        link.addEventListener("click", event => {
            event.preventDefault();
            showMessage("Submission status details will be available when the My Articles page is connected.", "info");
            const message = document.getElementById("formMessage");
            if (message) message.scrollIntoView({behavior: "smooth", block: "center"});
        });
    });

    // Initial page loading
    loadArticleCounts();

});

