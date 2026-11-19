<?php
require_once __DIR__ . '/includes/bootstrap.php';

$degreeOptions = [
    'Faculty of Business' => [
        'BM (Honors) in Business Analytics',
        'BM (Honors) in Applied Economics',
        'BBM (Hons) Tourism, Hospitality & Events',
        'BSc in Multimedia',
        'BA in Business Communication',
        'BM (Hons) in Accounting and Finance',
        'BMgtHons (International Business)',
        'BSc in Business Management (Industrial Management) (Special)',
        'BSc in Business Management (Project Management) (Special)',
        'BSc in Business Management (Logistics Management) (Special)',
        'BSc in Business Management (Human Resource Management) (Special)',
        'Bachelor of Laws (Honours)',
        'BM (Hons) in Marketing Management',
        'BM (Hons) in Law and Business Studies',
        'BM (Hons.) in Law and International Trade',
        'BM (Hons) in Law and E-Commerce',
        'Foundation Programme for Bachelor’s Degree',
    ],
    'Faculty of Computing' => [
        'BSc (Hons) Data Science – Plymouth',
        'BSc (Hons) Artificial Intelligence – Plymouth',
        'BSc (Honours) Computer Science – UGC',
        'BSc (Honours) in Technology Management',
        'BSc (Honours) in Software Engineering',
        'BSc in Management Information Systems (Special)',
        'BSc (Hons) Computer Networks – Plymouth',
        'BSc (Hons) Computer Security',
        'BSc (Honours) in Computer Networks – UGC',
    ],
    'Faculty of Engineering' => [
        'BSc (Hons) Quantity Surveying',
        'BSc (Hons) Quantity Surveying Top-Up Degree',
        'BA (Hons) in Interior Design',
        'BEng (Hons) Electrical, Electronics, and Communication Engineering',
        'BEng (Hons) Electrical, Electronic and Communication Engineering Top-Up Degree',
        'BEng (Hons) Civil and Structural Engineering',
        'BEng (Hons) Civil and Structural Engineering Top-Up Degree',
        'BEng (Hons) Mechanical and Mechatronics',
        'BEng (Hons) Robotics and Automation Engineering',
        'BEng (Hons) Robotics and Automation Engineering – Top Up Degree',
        'BEng (Hons) Electronic and Computer Engineering',
        'BEng (Hons) in Automotive Engineering',
        'BEng (Hons) Naval Architecture and Marine Engineering',
    ],
    'Faculty of Science' => [
        'BSc (Hons) Nutrition and Health',
        'BSc (Hons) in Pharmaceutical Science',
        'BSc (Hons) in Biomedical Science',
        'BSc (Hons) Nursing – Top up',
        'BSc (Hons) Biomedical Science – University of Plymouth UK',
        'BSc (Hons) Nursing – University of Plymouth UK',
        'BSc (Hons) Psychology – University of Plymouth UK',
    ],
    'Faculty of Postgraduate Studies and Professional Advancement' => [
        'Master of Education  in Educational Management & Leadership [M.Ed]',
        'Master of Business Studies (MBS)',
        'Master of Information Technology (MIT) and Master of Science in Information Technology (MSc in IT)',
        'Master of Business Administration (MBA)',
        'Postgraduate Diploma In Business Management',
        'Postgraduate Diploma In Human Resource Management',
        'Postgraduate Diploma in Computer Networks',
        'Postgraduate Diploma in Software Engineering',
        'Postgraduate Diploma In Industrial Management',
        'Postgraduate Diploma in Project Management',
    ],
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_post();
    verify_csrf();
    $name = trim(posted_string('fullname'));
    $studentId = trim(posted_string('student_id'));
    $email = strtolower(trim(posted_string('email')));
    $faculty = trim(posted_string('faculty'));
    $degree = trim(posted_string('degree'));
    $intake = trim(posted_string('intake'));
    $password = posted_string('password');
    $confirm = posted_string('confirm');
    $faculties = [
        'Faculty of Business',
        'Faculty of Computing',
        'Faculty of Engineering',
        'Faculty of Science',
        'Faculty of Postgraduate Studies and Professional Advancement',
        'Staff',
    ];
    $intakes = [];
    $currentYear = (int) date('y');
    for ($yearOffset = 3; $yearOffset >= 0; $yearOffset--) {
        $year = ($currentYear - $yearOffset) % 100;
        for ($number = 1; $number <= 3; $number++) {
            $intakes[] = sprintf('%02d.%d', $year, $number);
        }
    }

    if (
        preg_match('/\A.{2,100}\z/us', $name) !== 1 ||
        ($faculty !== 'Staff' && (mb_strlen($studentId) < 1 || mb_strlen($studentId) > 50)) ||
        strlen($email) > 150 ||
        !is_nsbm_email($email) ||
        !in_array($faculty, $faculties, true) ||
        ($faculty !== 'Staff' && !in_array($degree, $degreeOptions[$faculty] ?? [], true)) ||
        ($faculty !== 'Staff' && !in_array($intake, $intakes, true)) ||
        strlen($password) < 8 || strlen($password) > 72 ||
        $password !== $confirm ||
        posted_string('terms') !== 'on'
    ) {
        redirect('register.php?error=invalid');
    }

    try {
        $query = database()->prepare(
                        "INSERT INTO users (name, student_id, email, faculty, degree, intake, password, terms_accepted_at, account_status)
             VALUES (:name, :student_id, :email, :faculty, :degree, :intake, :password, CURRENT_TIMESTAMP, 'pending')"
        );
        $query->execute([
            'name' => $name,
            'student_id' => $faculty === 'Staff' ? null : $studentId,
            'email' => $email,
            'faculty' => $faculty,
            'degree' => $faculty === 'Staff' ? null : $degree,
            'intake' => $faculty === 'Staff' ? null : $intake,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
                $_SESSION['registration_pending'] = true;
        redirect('login.php');
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            if (str_contains((string) ($exception->errorInfo[2] ?? ''), 'uq_users_student_id')) {
                redirect('register.php?error=student-id');
            }
            redirect('register.php?error=email');
        }
        error_log('NewsDesk registration database error: ' . $exception->getMessage());
        redirect('register.php?error=unavailable');
    } catch (RuntimeException $exception) {
        error_log('NewsDesk registration configuration error: ' . $exception->getMessage());
        redirect('register.php?error=unavailable');
    }
}

$notice = match ($_GET['error'] ?? '') {
    'email' => 'That email address is already registered.',
    'student-id' => 'That Student ID is already registered.',
    'invalid' => 'Please check your details and try again.',
    'unavailable' => 'Registration is temporarily unavailable. Please try again later.',
    default => '',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&family=Roboto+Mono&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0" rel="stylesheet">
<link rel="stylesheet" href="register.css?v=back-home-1">
<title>Register | NSBM NewsDesk</title>
<link rel="stylesheet" href="../app/icons.css?v=1">
</head>
<body class="theme-dark">

<main class="auth-hero register">
  <div class="login-shell">
    <aside class="login-feature" aria-label="Join NSBM NewsDesk">
      <div class="feature-top"><span class="material-symbols-outlined">auto_stories</span> NSBM NEWSDESK</div>
      <div class="feature-art feature-video" aria-label="NSBM campus logo animation">
        <video class="mouse-scrub-video" autoplay loop muted playsinline preload="metadata" aria-label="Animated NSBM logo">
          <source src="videos/NSBM_Logo_Transparent.webm" type="video/webm">
        </video>
      </div>
      <div class="feature-copy"><span class="feature-kicker">YOUR STORY STARTS HERE.</span>
        <h2>CONNECT.<br>SHARE. GROW.</h2>
        <p>Create your campus account, share your stories, and be part of the NSBM community.</p>
      </div>
    </aside>
  <section class="glass-card login-panel">
      <a class="register-back-home" href="../index.php">
        <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
        <span>Back to Home</span>
      </a>
      <div class="logo-tile"><span class="material-symbols-outlined">newspaper</span></div>
    <h1>Join NSBM NewsDesk</h1>
    <p class="sub">Create an account to submit stories, bookmark articles,<br>and join community discussions</p>

    <?php if ($notice !== ''): ?><p role="alert" style="color:#ffb4b4;margin:0 0 16px"><?= e($notice) ?></p><?php endif; ?>
    <form id="registerForm" action="register.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="field">
        <label for="fullname">Full Name</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">person</span>
          <input type="text" id="fullname" name="fullname" maxlength="100" placeholder="Shagilan Jeyaraj" required>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field" id="studentIdField">
        <label for="student_id">Student ID</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">badge</span>
          <input type="text" id="student_id" name="student_id" maxlength="50" placeholder="Enter your student ID" required>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="email">Outlook Email</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">mail</span>
          <input type="email" id="email" name="email" maxlength="150" placeholder="username@students.nsbm.ac.lk" required>
        </div>
        <p class="hint">Use a valid NSBM student or staff email address (for example, @students.nsbm.ac.lk).</p>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="faculty">Faculty</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">school</span>
          <select id="faculty" name="faculty" required>
            <option value="" selected disabled>Select your faculty</option>
            <option>Faculty of Business</option>
            <option>Faculty of Computing</option>
            <option>Faculty of Engineering</option>
            <option>Faculty of Science</option>
            <option>Faculty of Postgraduate Studies and Professional Advancement</option>
            <option>Staff</option>
          </select>
          <span class="material-symbols-outlined chevron">expand_more</span>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="degree">Degree</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">school</span>
          <select id="degree" name="degree" disabled>
            <option value="" selected disabled>Select your degree</option>
          </select>
          <span class="material-symbols-outlined chevron">expand_more</span>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="intake">Intake</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">calendar_month</span>
          <select id="intake" name="intake" required>
            <option value="" selected disabled>Select your intake</option>
            <?php
            $currentYear = (int) date('y');
            for ($yearOffset = 3; $yearOffset >= 0; $yearOffset--):
                $year = ($currentYear - $yearOffset) % 100;
                for ($number = 1; $number <= 3; $number++):
                    $intakeOption = sprintf('%02d.%d', $year, $number);
            ?>
              <option value="<?= e($intakeOption) ?>"><?= e($intakeOption) ?></option>
            <?php
                endfor;
            endfor;
            ?>
          </select>
          <span class="material-symbols-outlined chevron">expand_more</span>
        </div>
        <div class="error-msg"></div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">lock</span>
          <input type="password" id="password" name="password" maxlength="72" placeholder="8–72 characters" autocomplete="new-password" required>
          <button type="button" class="toggle-pass" data-target="password" aria-label="Show password"><span class="material-symbols-outlined">visibility</span></button>
        </div>
        <div class="error-msg"></div>
        <div class="strength" aria-live="polite"><div class="strength-track"><span id="strengthBar"></span></div><small id="strengthLabel">Use 8+ characters with upper/lowercase, numbers and symbols.</small></div>
      </div>

      <div class="field">
        <label for="confirm">Confirm Password</label>
        <div class="input-wrap">
          <span class="material-symbols-outlined">lock_reset</span>
          <input type="password" id="confirm" name="confirm" maxlength="72" placeholder="Confirm your password" autocomplete="new-password" required>
          <button type="button" class="toggle-pass" data-target="confirm" aria-label="Show password"><span class="material-symbols-outlined">visibility</span></button>
        </div>
        <div class="error-msg"></div>
      </div>

      <label class="check terms">
        <input type="checkbox" id="terms" name="terms" required>
        <span>I agree to the <a href="guidelines.html">Community Guidelines</a> and <a href="terms.html">Terms of Service</a> for NSBM NewsDesk.</span>
      </label>
      <div class="field has-error" id="termsField" style="margin:-10px 0 14px"><div class="error-msg"></div></div>

      <button type="submit" class="btn-primary">Create Account <span class="material-symbols-outlined">arrow_forward</span></button>
      <script type="application/json" id="degreeOptions"><?= json_encode($degreeOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    </form>

    <p class="switch-line">Already have an account? <a href="login.php">Login here</a></p>
  </section>
  </div>
</main>

<script src="js/register.js?v=degree-2" defer></script>
</body>
</html>
