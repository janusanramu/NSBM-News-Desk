<?php
declare(strict_types=1);
require_once __DIR__.'/app/render.php';
require_user_role('student',url_for('NSBM-News-Desk-feature-login-register-page/login.php'));
render_original('submissions/my-submissions.html','submissions','student');
