<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
?>
<!doctype html>
<html lang="en">
<!-- Authenticated dashboard -->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#00563f">
  <title>NSBM NewsDesk | User Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <script src="script.js" defer></script>
</head>
<body>
  <main class="dashboard">
    <section class="profile-banner" aria-label="User profile">
      <div class="profile-identity">
        <div class="avatar-wrap"><img src="assets/profile.png" alt="Profile photo" class="avatar"><span class="verified" aria-label="Contributor badge">✿</span></div>
        <div class="profile-copy">
          <div class="name-row"><h1>Your Profile</h1><span class="role">Editor / Contributor</span></div>
          <p class="faculty">Faculty of Computing • Software Engineering (Batch 22)</p>
          <p class="membership"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> Member since October 2022 • Last active 2 hours ago</p>
        </div>
      </div>
      <div class="stats" aria-label="Profile statistics">
        <div class="stat"><strong data-count="14">14</strong><span>SUBMISSIONS</span></div>
        <div class="stat"><strong data-count="2400" data-format="compact">2.4k</strong><span>READS</span></div>
        <div class="stat"><strong class="saved-number" data-count="38">38</strong><span>SAVED</span></div>
      </div>
    </section>

    <nav class="tabs" aria-label="Dashboard navigation">
      <button class="tab active" data-tab="overview" aria-current="page"><span class="tab-icon">▦</span> Overview</button>
      <button class="tab" data-tab="submissions"><span class="tab-icon">▤</span> My Submissions</button>
      <button class="tab" data-tab="bookmarks"><span class="tab-icon"><svg class="nav-shape-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2.5h12v19l-6-4.5-6 4.5z" fill="currentColor"/></svg></span> Saved Bookmarks</button>
      <button class="tab" data-tab="submit"><span class="tab-icon">▧</span> Submit Article</button>
      <button class="tab" data-tab="settings"><span class="tab-icon">⚙</span> Account Settings</button>
      <button class="tab" data-tab="subscription" aria-controls="subscription" aria-expanded="false"><span class="tab-icon"><svg class="nav-shape-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v12l-5 5h-4l-3 2v-2H4z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="miter"/><path d="M9 8v5m6-5v5" stroke="currentColor" stroke-width="2.5"/></svg></span> My Subscriptions</button>
    </nav>

    <div class="dashboard-grid" id="overview" aria-label="Overview">
      <section class="card activity-card">
        <header class="card-header"><h2><span class="heading-icon">◴</span> Recent Activity</h2><span class="period">Last 30 days</span></header>
        <div class="timeline">
          <article class="activity"><div class="activity-icon publish" aria-hidden="true">↟</div><div><h3>Article published: "NSBM Inter-Faculty Hackathon Kickoff"</h3><p>Your submission was approved and published by the editorial board.</p><time>2 days ago</time></div></article>
          <article class="activity"><div class="activity-icon bookmark" aria-hidden="true">♧</div><div><h3>Saved article: "Green Walk 2024 Route Map Announced"</h3><p>Added to your personal reading list bookmarks.</p><time>4 days ago</time></div></article>
          <article class="activity"><div class="activity-icon edit" aria-hidden="true">✎</div><div><h3>Profile updated</h3><p>Changed preferred faculty tag and notification settings.</p><time>1 week ago</time></div></article>
        </div>
      </section>
      <section class="card engagement-card">
        <header class="card-header"><h2><span class="heading-icon">↗</span> Engagement Score</h2></header>
        <p class="engagement-subtitle">Reader reach across all published student articles.</p>
        <div class="engagement-value"><strong>2,480</strong><span>↑ +18%</span></div>
        <div class="chart" role="img" aria-label="Seven-bar engagement chart showing increasing reader reach"><span style="--h:40%"></span><span style="--h:59%"></span><span style="--h:54%"></span><span style="--h:79%"></span><span style="--h:69%"></span><span style="--h:94%"></span><span class="chart-last" style="--h:100%"></span></div>
        <p class="updated">Updated daily at midnight</p>
      </section>
    </div>
    <section class="subscription-panel card" id="subscription" aria-label="My News Category Subscriptions" hidden>
      <div class="subscription-top">
        <div><span class="subscription-eyebrow">PERSONALIZED NEWS NOTIFICATIONS</span><h2>My Subscriptions</h2><p>Follow the news categories you care about and manage their notifications.</p></div>
        <span class="subscription-status" id="subscription-status">0 categories followed</span>
      </div>
      <div class="subscription-tools">
        <label for="category-search">Find a news category</label>
        <input id="category-search" type="search" placeholder="Search news categories..." autocomplete="off">
      </div>
      <div class="subscription-list" id="subscription-list" aria-live="polite"></div>
      
      <p class="subscription-feedback" id="subscription-feedback" role="status" aria-live="polite"></p>
    </section>
<section class="settings-panel" id="settings" hidden aria-label="Account Settings">
 <div class="settings-intro"><div><span class="subscription-eyebrow">PERSONAL WORKSPACE</span><h2>Account Settings</h2><p>Keep your student profile and NewsDesk experience up to date.</p></div></div>
 <div class="settings-layout">
  <aside class="settings-sidebar" aria-label="Settings navigation"><div class="settings-sidebar-title">SETTINGS MENU</div>
   <button type="button" class="settings-side-link selected" data-section="profile"><span>◉</span><span>My Profile<small>Personal details & photo</small></span></button>
   <button type="button" class="settings-side-link" data-section="security"><span>♢</span><span>Security<small>Password & protection</small></span></button>
   <button type="button" class="settings-side-link" data-section="reading"><span>▤</span><span>Reading Preferences<small>Content interests</small></span></button>
   <button type="button" class="settings-side-link" data-section="notifications"><span class="settings-nav-bell"><svg class="settings-bell-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg></span><span>Notifications<small>Alerts & updates</small></span></button>
   <div class="settings-side-note"><strong>News category subscriptions</strong><p>Manage followed categories separately from reading interests.</p><button type="button" id="goSubscriptions">Manage subscriptions →</button></div>
  </aside>
  <div class="settings-content"><form id="settingsForm" novalidate>
   <div class="settings-view" data-view="profile"><div class="settings-view-heading"><span class="settings-kicker">01 / PROFILE</span><h3>My Profile</h3><p>Manage how your profile appears across NewsDesk.</p></div>
    <section class="settings-card"><div class="settings-card-title"><span class="settings-icon">◎</span><div><h4>Profile photo</h4><p>Choose a photo that represents you.</p></div></div>
     <div class="modern-photo-row"><img src="assets/profile.png" id="photoPreview" alt="Profile photo preview"/><div><div class="photo-buttons"><label class="save-button photo-pick" for="profilePhoto">Choose photo</label><button type="button" class="remove-button" id="removePhoto">Remove</button></div><input type="file" id="profilePhoto" accept="image/jpeg,image/png,image/webp" hidden/><small>JPG, PNG or WebP • Maximum 2 MB.</small></div></div>
    </section>
    <section class="settings-card"><div class="settings-card-title"><span class="settings-icon">♙</span><div><h4>Personal information</h4><p>Your student and contact details.</p></div></div>
     <div class="form-grid"><div class="form-group"><label for="fullName">Full name</label><input id="fullName" name="fullName" required value=""/></div><div class="form-group"><label for="studentId">Student ID <span class="field-lock">Locked</span></label><input id="studentId" name="studentId" value="" readonly/><small>Contact administration to change your ID.</small></div><div class="form-group"><label for="email">Email address</label><input id="email" name="email" type="email" required value=""/></div><div class="form-group"><label for="faculty">Faculty</label><select id="faculty" name="faculty" required><option>Faculty of Computing</option><option>Faculty of Business</option><option>Faculty of Engineering</option><option>Faculty of Science</option><option>Faculty of Postgraduate &amp; Professional Advancement</option></select></div><div class="form-group"><label for="degree">Degree programme</label><select id="degree" name="degree" required></select></div><div class="form-group"><label for="batchYear">Batch year</label><select id="batchYear" name="batchYear" required><option value="">Select batch year</option></select></div></div>
    </section>
   </div>
   <div class="settings-view" data-view="security" hidden><div class="settings-view-heading"><span class="settings-kicker">02 / SECURITY</span><h3>Security</h3><p>Protect your account with a strong password.</p></div><section class="settings-card"><div class="settings-card-title"><span class="settings-icon">♢</span><div><h4>Change password</h4><p>All password fields are optional unless you're changing your password.</p></div></div><div class="form-grid"><div class="form-group full-width"><label for="currentPassword">Current password</label><div class="password-wrap"><input type="password" id="currentPassword" autocomplete="current-password" placeholder="Enter current password"/><button type="button" class="password-eye" data-target="currentPassword" aria-label="Show current password">Show</button></div></div><div class="form-group"><label for="newPassword">New password</label><div class="password-wrap"><input type="password" id="newPassword" autocomplete="new-password" minlength="8" placeholder="At least 8 characters"/><button type="button" class="password-eye" data-target="newPassword" aria-label="Show new password">Show</button></div><div class="strength-track"><span id="strengthFill"></span></div><small id="strengthLabel">Use 8+ characters, numbers and symbols.</small></div><div class="form-group"><label for="confirmPassword">Confirm new password</label><div class="password-wrap"><input type="password" id="confirmPassword" autocomplete="new-password" placeholder="Repeat new password"/><button type="button" class="password-eye" data-target="confirmPassword" aria-label="Show confirmed password">Show</button></div></div></div></section></div>
   <div class="settings-view" data-view="reading" hidden><div class="settings-view-heading"><span class="settings-kicker">03 / PERSONALIZATION</span><h3>Reading Preferences</h3><p>Choose topics you'd like NewsDesk to prioritize in your reading experience.</p></div><section class="settings-card"><div class="settings-card-title"><span class="settings-icon">▤</span><div><h4>Topics of interest</h4><p>These are reading interests, not notification subscriptions.</p></div></div><div class="modern-choice-list"><label class="modern-choice"><span><strong>Academic</strong><small>Exams, academic updates and university notices</small></span><input type="checkbox" name="categories[]" value="academic" checked/></label><label class="modern-choice"><span><strong>Events</strong><small>Campus events, workshops and competitions</small></span><input type="checkbox" name="categories[]" value="events" checked/></label><label class="modern-choice"><span><strong>Sports</strong><small>Sports news, matches and student achievements</small></span><input type="checkbox" name="categories[]" value="sports"/></label><label class="modern-choice"><span><strong>General News</strong><small>News and updates from across NSBM</small></span><input type="checkbox" name="categories[]" value="general" checked/></label></div></section></div>
   <div class="settings-view" data-view="notifications" hidden><div class="settings-view-heading"><span class="settings-kicker">04 / NOTIFICATIONS</span><h3>Notifications</h3><p>Decide which NewsDesk updates you want to hear about.</p></div><section class="settings-card"><div class="settings-card-title"><span class="settings-icon"><svg class="settings-bell-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg></span><div><h4>Notification controls</h4><p>Customize your alerts. Choose the alerts you want to receive.</p></div></div><div class="modern-choice-list"><label class="modern-choice"><span><strong>News updates</strong><small>Updates from your subscribed news categories</small></span><input type="checkbox" name="newsNotifications" checked/></label><label class="modern-choice"><span><strong>Article submission updates</strong><small>When your articles are approved or rejected</small></span><input type="checkbox" name="submissionNotifications" checked/></label><label class="modern-choice"><span><strong>Comment notifications</strong><small>When readers interact with your published articles</small></span><input type="checkbox" name="commentNotifications"/></label><label class="modern-choice"><span><strong>Email notifications</strong><small>Receive eligible alerts by email as well</small></span><input type="checkbox" name="emailNotifications" checked/></label></div></section></div>
   <div class="settings-action-bar"><span id="settingsDirty" aria-live="polite">No unsaved changes</span><div><button type="button" class="cancel-button" id="cancelSettings">Discard</button><button type="submit" class="save-button" id="saveSettings">Save changes</button></div></div>
   <p class="settings-feedback" id="settingsFeedback" role="status" aria-live="polite"></p>
  </form></div>
 </div>
</section>
  </main>
</body>
</html>
