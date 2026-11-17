<?php
require_once __DIR__ . '/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';
require_admin('../admin-login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSBM NewsDesk - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css?v=merged-2">
    <link rel="stylesheet" href="assets/css/admin-settings.css?v=profile-1">
    <link rel="stylesheet" href="assets/css/admin-light-background.css?v=20261106-1">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>try{document.documentElement.dataset.theme=localStorage.getItem("nsbmNewsDeskTheme")==="dark"?"dark":"light";}catch(e){document.documentElement.dataset.theme="light";}</script>
<link rel="stylesheet" href="../app/icons.css?v=1">
</head>
<body>
<div class="ambient-orb ambient-orb-one" aria-hidden="true"></div><div class="ambient-orb ambient-orb-two" aria-hidden="true"></div>

<div class="layout">

    <!-- SIDEBAR -->
    <button type="button" class="mobile-sidebar-toggle" id="sidebarToggle" aria-controls="adminSidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i> Admin Menu <i class="fa-solid fa-chevron-down"></i></button>
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-heading"><img src="assets/images/branding/nsbm-logo.jpg" alt="NSBM" class="admin-brand-logo"> ADMIN PORTAL</div>

        <a href="#dashboard" class="side-link active" data-page="dashboard">
            <i class="fa-solid fa-table-cells-large"></i> Dashboard
        </a>

        <a href="#articles" class="side-link" data-page="articles">
            <i class="fa-solid fa-file-lines"></i> Manage Articles
        </a>

        <a href="#comments" class="side-link" data-page="comments">
            <i class="fa-solid fa-comments"></i>
            <span>Manage Comments</span>
        </a>
        



        <a href="#categories" class="side-link" data-page="categories">
            <i class="fa-solid fa-layer-group"></i> Categories
        </a>

        <a href="#announcements" class="side-link" data-page="announcements">
            <i class="fa-solid fa-bullhorn"></i> Announcements
        </a>

        <a href="#reports" class="side-link" data-page="reports">
            <i class="fa-solid fa-chart-column"></i> Reports
        </a>

        <a href="#admin-management" class="side-link" data-page="admin-management"><i class="fa-solid fa-users-gear"></i> Admin Management</a>
        <a href="#student-approvals" class="side-link" data-page="student-approvals"><i class="fa-solid fa-user-check"></i> Student Approvals <span class="count-badge" id="approvalsBadge" hidden>0</span></a>
        <a href="#settings" class="side-link" data-page="settings"><i class="fa-solid fa-user-gear"></i> Admin Profile Settings</a>
        <div class="sidebar-theme"><span><i class="fa-solid fa-palette"></i> Appearance</span><button type="button" id="themeToggle" class="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false"><i class="fa-solid fa-sun" id="themeIcon"></i><span id="themeLabel">Light mode</span><i class="fa-solid fa-arrow-right-arrow-left"></i></button></div>
        <div class="system-card">
            <h3><i class="fa-solid fa-circle-check"></i> Campus Preview</h3>

            <img src="assets/images/branding/nsbm-campus.jpg"
                 alt="NSBM Green University campus">

            <strong>NSBM Green University</strong>
            <p>NSBM NewsDesk design workspace.</p><div class="system-line"><span><i class="fa-solid fa-circle-dot"></i> Workspace</span><span class="online">Active</span></div>
        </div>

        <div class="sidebar-bottom">
            <strong>Green Minds, Brighter Futures</strong>
            <p>NSBM NewsDesk Admin Portal</p>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- ADMIN OVERVIEW -->
        <section id="dashboardPage" class="page-section">
            <div class="page-heading">
                <div>
                    <h1>Admin Overview</h1>
                    <p>Welcome back, Administrator. Here's what's happening across NSBM NewsDesk today.</p>
                </div>

                <button class="primary-button" id="quickAnnouncement">
                    <i class="fa-solid fa-plus"></i> New Announcement
                </button>
            </div>

            <div class="stats-grid overview-stats">
                <article class="stat-card stat-card-featured">
                    <span>TOTAL ARTICLES</span>
                    <i class="fa-solid fa-file-lines stat-icon green"></i>
                    <h2 id="totalArticles">0</h2><p>Articles in the workspace</p><div class="stat-track"><span id="totalArticleTrack"></span></div>
                </article>

                <article class="stat-card">
                    <span>READERSHIP</span>
                    <i class="fa-solid fa-eye stat-icon blue"></i>
                    <h2 id="readershipCount">0</h2><p>Total article views</p><div class="stat-microline" aria-hidden="true"></div>
                </article>

                <article class="stat-card">
                    <span>STUDENTS</span>
                    <i class="fa-solid fa-users stat-icon purple"></i>
                    <h2 id="studentCount">0</h2><p>Registered student accounts</p><div class="stat-microline" aria-hidden="true"></div>
                </article>

                <article class="stat-card">
                    <span>PENDING REVIEW</span>
                    <i class="fa-solid fa-clock stat-icon red"></i>
                    <h2 id="pendingCount">0</h2><p>Waiting for editorial review</p><div class="stat-track"><span id="pendingArticleTrack"></span></div>
                </article>
            </div>

            <section class="content-card review-card">
                <div class="card-heading">
                    <div>
                        <h2>Pending Article Submissions</h2>
                        <p>Review student articles before publishing.</p>
                    </div>
                    <span class="count-badge" id="pendingLabel">0 Pending</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Article Title</th>
                                <th>Author / Club</th>
                                <th>Category</th>
                                <th>Submitted Date</th>
                                </tr>
                        </thead>
                        <tbody id="pendingTable"></tbody>
                    </table>
                </div>
            </section>
        </section>

        <!-- MANAGE ARTICLES -->
        <section id="articlesPage" class="page-section hidden">
            <div class="page-heading">
                <div>
                    <h1>Manage Articles</h1>
                    <p>Search and review article submissions.</p>
                </div>
                <button class="primary-button" id="newDraftButton" type="button"><i class="fa-solid fa-file-pen"></i> New Draft</button>
            </div>

            <div class="content-card">
                <div class="filter-row">
                    <input type="search" id="articleSearch" aria-label="Search articles"
                           placeholder="Search articles...">

                    <select id="statusFilter">
                        <option value="all">All statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="Draft">Draft</option>
                        <option value="Published">Published</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Article Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="articlesTable"></tbody>
                    </table>
                </div>
                <p id="emptyArticles" class="empty-message hidden">
                    No matching articles found.
                </p>
            </div>
        </section>

        <!-- CATEGORIES -->
        <section id="categoriesPage" class="page-section hidden">
            <div class="page-heading">
                <div>
                    <h1>Categories</h1>
                    <p>Categories used to organise campus news.</p>
                </div>
            </div>
            <div id="categoryGrid" class="category-grid"></div>
        </section>

        <!-- ANNOUNCEMENTS -->
        <section id="announcementsPage" class="page-section hidden">
            <div class="page-heading">
                <div>
                    <h1>Announcements</h1>
                    <p>View and manage university announcements.</p>
                </div>
                <button class="primary-button" id="newAnnouncement">
                    <i class="fa-solid fa-plus"></i> New Announcement
                </button>
            </div>

            <div class="content-card">
                <div class="filter-row">
                    <input type="search" id="announcementSearch" aria-label="Search announcements"
                           placeholder="Search announcements...">
                    <select id="announcementFilter">
                        <option value="all">All announcements</option>
                        <option value="Published">Published</option>
                        <option value="Draft">Draft</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div id="announcementList" class="announcement-list"></div>
            </div>
        </section>

        <!-- REPORTS -->
        <section id="reportsPage" class="page-section hidden">
            <div class="page-heading">
                <div>
                    <h1>Reports</h1>
                    <p>Article statistics and submission summaries.</p>
                </div>
                <button class="primary-button" id="downloadReport">
                    <i class="fa-solid fa-download"></i> Download Report
                </button>
            </div>

            <div class="stats-grid report-stats">
                <article class="stat-card">
                    <span>TOTAL ARTICLES</span>
                    <h2 id="reportTotal">0</h2>
                </article>
                <article class="stat-card">
                    <span>PENDING</span>
                    <h2 id="reportPending">0</h2>
                </article>
                <article class="stat-card">
                    <span>PUBLISHED</span>
                    <h2 id="reportPublished">0</h2>
                </article>
                <article class="stat-card">
                    <span>REJECTED</span>
                    <h2 id="reportRejected">0</h2>
                </article>
                <article class="stat-card"><span>DRAFTS</span><h2 id="reportDraft">0</h2></article>
            </div>

            <div class="report-insights">
  <article class="report-hero"><div class="report-hero-top"><span><i class="fa-solid fa-chart-pie"></i> CONTENT PERFORMANCE</span><span class="report-live">Current workspace data</span></div><h2>Editorial health at a glance</h2><p>Track publishing progress, pending reviews and category coverage.</p><div class="report-progress"><div id="reportProgressFill"></div></div><div class="report-hero-bottom"><strong id="reportPublishRate">0% published</strong><span id="reportRemaining">0 awaiting publication</span></div></article>
  <article class="report-spotlight"><div class="spotlight-icon"><i class="fa-solid fa-bolt"></i></div><span>EDITORIAL SNAPSHOT</span><h3 id="reportSpotlight">No articles yet</h3><p id="reportSpotlightDetail">Your activity summary will appear here.</p></article>
</div>
<div class="editorial-insights" id="editorialInsights" aria-live="polite"><div class="insight-heading"><span class="eyebrow">EDITORIAL INTELLIGENCE</span><h2>What needs your attention</h2><p>Insights calculated from current article records.</p></div><div class="insight-items" id="insightItems"></div></div>
<div class="chart-grid">
                <article class="content-card chart-card">
                    <div class="chart-heading"><div><span class="eyebrow">DISTRIBUTION</span><h2>Article Status Overview</h2><p>How articles move through review</p></div><i class="fa-solid fa-chart-pie"></i></div>
                    <canvas id="statusChart"></canvas>
                </article>

                <article class="content-card chart-card">
                    <div class="chart-heading"><div><span class="eyebrow">COVERAGE</span><h2>Articles by Category</h2><p>Content across campus topics</p></div><i class="fa-solid fa-chart-column"></i></div>
                    <canvas id="categoryChart"></canvas>
                </article>
            </div>
        </section>


<section id="admin-managementPage" class="page-section hidden">
 <div class="page-heading"><div><h1>Admin Management</h1><p>Manage administrator accounts and access.</p></div><button type="button" id="addAdminButton" class="primary-button"><i class="fa-solid fa-user-plus"></i> Add Admin</button></div>
 <div class="stats-grid admin-stat-grid"><article class="stat-card admin-stat-card"><span>TOTAL ADMINS</span><i class="fa-solid fa-users stat-icon green"></i><h2 id="adminTotal">—</h2></article><article class="stat-card admin-stat-card"><span>REGISTERED THIS MONTH</span><i class="fa-solid fa-user-plus stat-icon blue"></i><h2 id="adminNew">—</h2></article></div>
 <section class="content-card admin-accounts-card"><div class="card-heading"><div><h2>Administrator Accounts</h2><p>Search registered administrators.</p></div></div><div class="filter-row"><input type="search" id="adminSearch" placeholder="Search name, email or admin ID" aria-label="Search administrators"></div><div class="table-wrap"><table><thead><tr><th>Administrator</th><th>Admin ID</th><th>Email</th><th>Created</th><th>Actions</th></tr></thead><tbody id="adminTable"><tr><td colspan="5">Loading administrators...</td></tr></tbody></table></div><p id="adminFeedback" class="admin-feedback" role="status" aria-live="polite"></p></section>
</section>

        <section id="student-approvalsPage" class="page-section hidden">
  <div class="page-heading"><div><h1>Student Approvals</h1><p>Confirm new registrations are genuine NSBM students before they can sign in.</p></div></div>
  <section class="content-card">
    <div class="filter-row">
      <button type="button" class="small-button active" data-approval-view="pending">Pending</button>
      <button type="button" class="small-button" data-approval-view="rejected">Rejected</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Faculty / Degree</th><th>Intake</th><th>Registered</th><th>Actions</th></tr></thead>
        <tbody id="approvalsTable"></tbody>
      </table>
    </div>
    <p id="approvalsEmpty" class="empty-message hidden">No students in this list.</p>
  </section>
</section>
        <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <!-- Manage Comments Page -->
<section id="commentsPage" class="page-section hidden">

    <div class="page-header">
        <h1>Manage Comments</h1>
        <p>Review and manage student comments.</p>
    </div>

    <div class="comment-stats">
        <div class="comment-stat">
            <h3 id="commentPendingCount">0</h3>
            <p>Pending</p>
        </div>

        <div class="comment-stat">
            <h3 id="approvedCount">0</h3>
            <p>Approved</p>
        </div>

        <div class="comment-stat">
            <h3 id="removedCount">0</h3>
            <p>Removed</p>
        </div>
    </div>

    <div class="comment-tabs">
        <button class="active" onclick="showComments('pending', this)">Pending</button>
        <button onclick="showComments('approved', this)">Approved</button>
        <button onclick="showComments('removed', this)">Removed</button>
    </div>

    <div id="commentsList"></div>

</section>
        <!-- ADMIN ACCOUNT SETTINGS -->
        <section id="settingsPage" class="page-section hidden admin-settings-page">
          <div class="page-heading"><div><h1>Account Settings</h1><p>Manage your administrator profile, security and preferences.</p></div></div>
          <div class="admin-settings-layout">
            <nav class="admin-settings-nav" aria-label="Account settings sections">
              <button type="button" class="as-nav active" data-settings-tab="profile"><i class="fa-regular fa-user"></i> My Profile</button>
              <button type="button" class="as-nav" data-settings-tab="security"><i class="fa-solid fa-shield-halved"></i> Security</button>
              <button type="button" class="as-nav" data-settings-tab="preferences"><i class="fa-solid fa-sliders"></i> Account Preferences</button>
              <button type="button" class="as-nav" data-settings-tab="notifications"><i class="fa-regular fa-bell"></i> Notifications</button>
            </nav>
            <div class="admin-settings-content">
              <form id="adminSettingsForm" novalidate>
                <div class="as-pane" data-settings-pane="profile">
                  <div class="as-card"><div class="as-card-title"><div class="as-icon"><i class="fa-regular fa-image"></i></div><div><h2>Profile Photo</h2><p>Choose the photo shown on your administrator profile.</p></div></div>
                    <div class="as-photo-row"><div class="as-avatar" id="asAvatar"><span id="asInitials">AD</span><img id="asPhotoPreview" alt="Administrator profile photo" hidden></div><div class="as-photo-actions"><button class="as-button as-primary as-upload" id="asUploadPhoto" type="button">Upload Photo</button><input type="file" id="asPhoto" accept="image/jpeg,image/png,image/webp" hidden><button class="as-button as-outline" type="button" id="asRemovePhoto" hidden>Remove Photo</button><small>JPG, JPEG, PNG or WebP, up to 5 MB</small></div></div>
                  </div>
                  <div class="as-card"><div class="as-card-title"><div class="as-icon"><i class="fa-regular fa-address-card"></i></div><div><h2>Personal Information</h2><p>Keep your administrator contact details up to date.</p></div></div>
                    <div class="as-fields"><label>Full Name<input id="asName" name="full_name" type="text" maxlength="120" value="Administrator" autocomplete="name" required></label><label>Email Address<input id="asEmail" name="email" type="email" maxlength="190" value="admin@nsbm.ac.lk" autocomplete="email" required></label><label>Contact Number<input id="asPhone" name="contact_number" type="tel" maxlength="30" placeholder="Enter contact number" autocomplete="tel"></label><label>Account Role<input value="Administrator" readonly aria-label="Account role"></label></div>
                  </div>
                </div>
                <div class="as-pane" data-settings-pane="security" hidden>
                  <div class="as-card"><div class="as-card-title"><div class="as-icon"><i class="fa-solid fa-lock"></i></div><div><h2>Change Password</h2><p>Change your account password securely.</p></div></div>
                    <div class="as-fields"><label class="as-full">Current Password<span class="as-password"><input id="asCurrent" type="password" autocomplete="current-password" placeholder="Enter current password"><button type="button" class="as-eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></span></label><label>New Password<span class="as-password"><input id="asNew" type="password" minlength="12" autocomplete="new-password" placeholder="At least 12 characters"><button type="button" class="as-eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></span></label><label>Confirm New Password<span class="as-password"><input id="asConfirm" type="password" minlength="12" autocomplete="new-password" placeholder="Repeat new password"><button type="button" class="as-eye" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></span></label></div><div class="as-strength"><div class="as-strength-track"><span id="asStrengthBar"></span></div><small id="asStrengthLabel">Enter a new password to check its strength</small></div>
                    <div class="as-info"><i class="fa-solid fa-circle-info"></i><span>Password changes are not available in this interface.</span></div>
                  </div>
                </div>
                <div class="as-pane" data-settings-pane="preferences" hidden>
                  <div class="as-card"><div class="as-card-title"><div class="as-icon"><i class="fa-solid fa-palette"></i></div><div><h2>Account Preferences</h2><p>Customize how you work with NewsDesk.</p></div></div>
                    <label class="as-field-single">Interface Theme<select id="asTheme" name="theme"><option value="light">Light</option><option value="dark">Dark</option><option value="system">System default</option></select></label>
                    <label class="as-switch-row"><span><strong>Compact Tables</strong><small>Show more article rows on each screen.</small></span><input id="asCompact" type="checkbox" role="switch"><span class="as-switch" aria-hidden="true"></span></label>
                  </div>
                </div>
                <div class="as-pane" data-settings-pane="notifications" hidden>
                  <div class="as-card"><div class="as-card-title"><div class="as-icon"><i class="fa-regular fa-bell"></i></div><div><h2>Notification Settings</h2><p>Choose which administration updates matter to you.</p></div></div>
                    <label class="as-switch-row"><span><strong>Email Notifications</strong><small>Receive important updates by email.</small></span><input id="asNotifyEmail" type="checkbox" role="switch" checked><span class="as-switch" aria-hidden="true"></span></label>
                    <label class="as-switch-row"><span><strong>New Article Submissions</strong><small>Be notified when students submit articles.</small></span><input id="asNotifyArticles" type="checkbox" role="switch" checked><span class="as-switch" aria-hidden="true"></span></label>
                    <label class="as-switch-row"><span><strong>Comments Awaiting Review</strong><small>Get alerts for comments requiring moderation.</small></span><input id="asNotifyComments" type="checkbox" role="switch" checked><span class="as-switch" aria-hidden="true"></span></label>
                    <label class="as-switch-row"><span><strong>Announcements</strong><small>Receive updates about campus announcements.</small></span><input id="asNotifyAnnouncements" type="checkbox" role="switch"><span class="as-switch" aria-hidden="true"></span></label>
                  </div>
                </div>
                <div class="as-actions"><button type="button" class="as-button as-outline" id="asReset">Cancel Changes</button><button type="submit" class="as-button as-primary"><i class="fa-solid fa-check"></i> Save Changes</button></div>
                <p id="asFeedback" class="as-feedback" role="status" aria-live="polite"></p>
              </form>
            </div>
          </div>
        </section>

    </main>
</div>

<!-- MODAL -->
<div id="modalBackdrop" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true"
         aria-labelledby="modalTitle">
        <button id="closeModal" class="modal-close"
                aria-label="Close">&times;</button>
        <h2 id="modalTitle">Details</h2>
        <div id="modalContent"></div>
    </div>
</div>

<div id="adminFormBackdrop" class="modal-backdrop hidden"><div class="modal admin-form-modal" role="dialog" aria-modal="true" aria-labelledby="adminFormTitle"><button type="button" id="closeAdminForm" class="modal-close" aria-label="Close">&times;</button><h2 id="adminFormTitle">Register New Administrator</h2><p class="admin-form-subtitle">Create an account with administrator access.</p><form id="adminRegistrationForm" autocomplete="off"><div class="admin-form-grid"><label>Full Name<input name="full_name" maxlength="120" required autocomplete="off" placeholder="Full name"></label><label>Admin ID<input name="admin_id" maxlength="80" required placeholder="e.g. ADM002"></label><label class="admin-full">Email Address<input name="email" type="email" maxlength="190" required placeholder="admin@nsbm.ac.lk"></label><label>Password<input name="password" type="password" minlength="12" maxlength="128" required autocomplete="new-password"></label><label>Confirm Password<input name="confirm_password" type="password" minlength="12" maxlength="128" required autocomplete="new-password"></label></div><p class="admin-form-hint">Use a unique password with at least 12 characters.</p><p id="adminFormError" class="admin-form-error" role="alert"></p><div class="modal-actions"><button type="button" id="cancelAdminForm" class="secondary-button">Cancel</button><button type="submit" class="primary-button" id="saveAdminButton">Create Admin</button></div></form></div></div>

<script src="assets/js/theme.js?v=theme-fix-2"></script><script src="assets/js/script.js?v=profile-1"></script><script src="assets/js/admin-management.js"></script><script src="assets/js/admin-settings.js?v=profile-1"></script><script src="assets/js/approvals.js?v=1"></script>
</body>
</html>
</body>
</html>