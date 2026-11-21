<?php
// ============================================================
//  login.php  —  simple sign-in (no CSRF)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/* ---------- If already logged in, go home ---------- */
if (!empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$oldEmail = '';

/* ---------- Handle form submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $oldEmail = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Validate
    if ($oldEmail === '' || !filter_var($oldEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    // Verify against database
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$oldEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Generic message — do not reveal whether email exists
            $errors[] = 'Those details don\'t match our records. Please try again.';
            usleep(300000); // slow brute-force attempts
        } else {
            // Rehash if algorithm has changed
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $up->execute([$newHash, $user['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            header('Location: ../index.php');
            exit;
        }
    }
}

/* ---------- Escape helper ---------- */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sign in to your Café Krunal account — track your favourite orders, reservations, and loyalty rewards.">
  <meta name="theme-color" content="#211914">
  <title>Sign In — Café Krunal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root {
      --ink: #211914;
      --coffee: #4a3024;
      --cream: #f4efe7;
      --paper: #fbf9f5;
      --tan: #c59b70;
      --muted: #83766b;
      --line: rgba(49, 35, 27, .14);
      --serif: "Playfair Display", Georgia, serif;
      --sans: "DM Sans", Arial, sans-serif;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      background: var(--paper);
      color: var(--ink);
      font-family: var(--sans);
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    a { color: inherit; text-decoration: none; }
    img { display: block; max-width: 100%; }

    .auth-shell {
      width: 100%;
      max-width: 1040px;
      background: #fffdf9;
      box-shadow: 0 30px 80px rgba(32, 23, 17, .12), 0 2px 6px rgba(32, 23, 17, .04);
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 640px;
      overflow: hidden;
      border: 1px solid var(--line);
    }

    .auth-visual {
      position: relative;
      background: #211914;
      color: #fff;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 40px;
    }
    .auth-visual-bg {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(160deg, rgba(22, 15, 11, .88) 0%, rgba(22, 15, 11, .5) 55%, rgba(22, 15, 11, .28) 100%),
        url("https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1200&q=90") center/cover;
      transform: scale(1.02);
    }
    .auth-visual > * { position: relative; z-index: 1; }

    .brand {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font: 600 1.3rem var(--serif);
      letter-spacing: -.04em;
      color: #fff;
    }
    .brand-mark {
      width: 34px; height: 34px;
      display: grid; place-items: center;
      border: 1px solid var(--tan);
      border-radius: 50%;
      color: #e5c5a2;
      font: 500 1rem var(--serif);
      flex: 0 0 auto;
    }

    .visual-quote {
      max-width: 340px;
      margin-top: auto;
      padding-top: 40px;
    }
    .visual-quote .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      color: #e5c5a2;
      font-size: .64rem;
      font-weight: 700;
      letter-spacing: .2em;
      text-transform: uppercase;
    }
    .visual-quote .eyebrow:before {
      content: "";
      width: 27px; height: 1px;
      background: var(--tan);
    }
    .visual-quote h2 {
      margin: 18px 0 14px;
      font: 400 clamp(1.8rem, 3.6vw, 2.9rem)/1.08 var(--serif);
      letter-spacing: -.05em;
    }
    .visual-quote h2 em { color: #d8b38d; font-style: normal; }
    .visual-quote p {
      color: rgba(255, 255, 255, .7);
      font-size: .85rem;
      line-height: 1.8;
      margin: 0;
    }

    .visual-foot {
      margin-top: 34px;
      padding-top: 18px;
      border-top: 1px solid rgba(255, 255, 255, .16);
      color: rgba(255, 255, 255, .55);
      font-size: .62rem;
      letter-spacing: .16em;
      text-transform: uppercase;
    }

    .auth-form-panel {
      padding: 52px 56px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .auth-kicker {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      color: var(--coffee);
      font-size: .64rem;
      font-weight: 700;
      letter-spacing: .2em;
      text-transform: uppercase;
    }
    .auth-kicker:before {
      content: "";
      width: 24px; height: 1px;
      background: var(--tan);
    }
    .auth-title {
      margin: 16px 0 8px;
      font: 400 clamp(1.9rem, 3.4vw, 2.6rem)/1.12 var(--serif);
      letter-spacing: -.045em;
    }
    .auth-sub {
      color: var(--muted);
      font-size: .85rem;
      line-height: 1.75;
      margin-bottom: 30px;
    }

    .form-label {
      color: #6e5d50;
      font-size: .63rem;
      font-weight: 700;
      letter-spacing: .12em;
      text-transform: uppercase;
      margin-bottom: 7px;
    }
    .form-control {
      min-height: 50px;
      border: 1px solid #ddd1c4;
      border-radius: 0;
      background: #fffdf9;
      font-size: .88rem;
      padding: 12px 14px;
      color: var(--ink);
    }
    .form-control::placeholder { color: #b1a495; }
    .form-control:focus {
      border-color: #a77b54;
      box-shadow: 0 0 0 .2rem rgba(167, 123, 84, .12);
      background: #fff;
    }

    .input-wrap { position: relative; }
    .input-wrap .form-control { padding-right: 46px; }
    .toggle-pass {
      position: absolute;
      top: 50%; right: 6px;
      transform: translateY(-50%);
      width: 38px; height: 38px;
      display: grid; place-items: center;
      border: 0; background: transparent;
      color: var(--muted);
      font-size: 1rem;
      cursor: pointer;
      border-radius: 50%;
      transition: .2s;
    }
    .toggle-pass:hover { color: var(--coffee); background: rgba(167, 123, 84, .1); }

    .form-row-between {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin: 16px 0 24px;
      flex-wrap: wrap;
    }
    .check {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      font-size: .78rem;
      color: #6e5d50;
      cursor: pointer;
      user-select: none;
      margin: 0;
    }
    .check input {
      width: 16px; height: 16px;
      accent-color: var(--coffee);
      cursor: pointer;
      margin: 0;
    }
    .link-quiet {
      font-size: .76rem;
      font-weight: 600;
      color: var(--coffee);
      border-bottom: 1px solid transparent;
      transition: .2s;
    }
    .link-quiet:hover { border-color: var(--tan); color: #b17d4d; }

    .btn-coffee {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      width: 100%;
      padding: 15px 22px;
      border: 1px solid var(--ink);
      background: var(--ink);
      color: #fff;
      border-radius: 0;
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: .13em;
      text-transform: uppercase;
      transition: .3s ease;
      cursor: pointer;
    }
    .btn-coffee:hover {
      background: var(--tan);
      border-color: var(--tan);
      color: var(--ink);
      transform: translateY(-2px);
    }
    .btn-coffee:active { transform: translateY(0); }

    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 26px 0;
      color: #a89a8d;
      font-size: .62rem;
      font-weight: 600;
      letter-spacing: .16em;
      text-transform: uppercase;
    }
    .divider:before, .divider:after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--line);
    }

    .social-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .social-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 12px 14px;
      border: 1px solid var(--line);
      background: transparent;
      color: var(--ink);
      font-size: .76rem;
      font-weight: 600;
      letter-spacing: .04em;
      transition: .25s;
      cursor: pointer;
    }
    .social-btn i { font-size: 1rem; color: var(--coffee); }
    .social-btn:hover {
      background: var(--cream);
      border-color: #cdb9a4;
      transform: translateY(-2px);
    }

    .auth-switch {
      margin-top: 28px;
      text-align: center;
      font-size: .8rem;
      color: var(--muted);
    }
    .auth-switch a {
      color: var(--coffee);
      font-weight: 700;
      border-bottom: 1px solid var(--tan);
      padding-bottom: 1px;
      transition: .2s;
    }
    .auth-switch a:hover { color: #b17d4d; }

    .form-note {
      margin-top: 16px;
      font-size: .72rem;
      color: var(--muted);
      line-height: 1.6;
      min-height: 18px;
    }
    .form-note.error { color: #a33a2a; }
    .form-note.success { color: #3d6b45; }

    .auth-alert {
      padding: 12px 14px;
      margin-bottom: 20px;
      background: #fbebe7;
      border-left: 3px solid #a33a2a;
      color: #7d2c1f;
      font-size: .82rem;
      line-height: 1.5;
    }
    .auth-alert ul { margin: 0; padding-left: 18px; }

    @media (max-width: 991px) {
      .auth-shell {
        grid-template-columns: 1fr;
        max-width: 520px;
        min-height: 0;
      }
      .auth-visual {
        padding: 30px 30px 26px;
        min-height: 230px;
      }
      .visual-quote { padding-top: 26px; }
      .visual-quote h2 { font-size: clamp(1.6rem, 5vw, 2.1rem); }
      .visual-quote p { font-size: .8rem; }
      .visual-foot { display: none; }
      .auth-form-panel { padding: 40px 30px; }
    }

    @media (max-width: 480px) {
      body { padding: 0; align-items: stretch; }
      .auth-shell { border: 0; box-shadow: none; max-width: 100%; min-height: 100vh; }
      .auth-visual { min-height: 210px; padding: 26px 22px 22px; }
      .visual-quote { padding-top: 18px; }
      .visual-quote .eyebrow { font-size: .58rem; }
      .visual-quote h2 { margin: 14px 0 10px; }
      .visual-quote p { font-size: .76rem; }
      .auth-form-panel { padding: 32px 22px 40px; }
      .auth-title { font-size: clamp(1.7rem, 7vw, 2.1rem); }
      .social-row { grid-template-columns: 1fr; }
      .form-row-between { gap: 10px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *:before, *:after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
      }
    }
  </style>
</head>
<body>

  <main class="auth-shell" role="main">

    <aside class="auth-visual" aria-hidden="false">
      <div class="auth-visual-bg"></div>

      <a class="brand" href="../index.php" aria-label="Café Krunal home">
        <span class="brand-mark">K</span> café krunal<span style="color:#b58a62">.</span>
      </a>

      <div class="visual-quote">
        <div class="eyebrow">A little pause in your everyday</div>
        <h2>Good coffee.<br><em>Better moments.</em></h2>
        <p>Sign in to save your favourite orders, manage reservations and keep track of every little coffee moment.</p>
      </div>

      <div class="visual-foot">Coffee · Conversation · Community</div>
    </aside>

    <section class="auth-form-panel">
      <div class="auth-kicker">Welcome back</div>
      <h1 class="auth-title">Sign in to your table.</h1>
      <p class="auth-sub">Your usual order, saved reservations and warm welcomes are just a step away.</p>

      <?php if ($errors): ?>
        <div class="auth-alert" role="alert">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="post" action="login.php" novalidate>
        <div class="mb-3">
          <label class="form-label" for="loginEmail">Email address</label>
          <input
            class="form-control"
            type="email"
            id="loginEmail"
            name="email"
            value="<?= e($oldEmail) ?>"
            placeholder="you@example.com"
            autocomplete="email"
            required
          >
        </div>

        <div class="mb-2">
          <label class="form-label" for="loginPassword">Password</label>
          <div class="input-wrap">
            <input
              class="form-control"
              type="password"
              id="loginPassword"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              minlength="6"
              required
            >
            <button
              class="toggle-pass"
              type="button"
              id="togglePassword"
              aria-label="Show password"
              aria-pressed="false"
            ><i class="bi bi-eye"></i></button>
          </div>
        </div>

        <div class="form-row-between">
          <label class="check">
            <input type="checkbox" name="remember" id="rememberMe">
            Remember me
          </label>
          <a class="link-quiet" href="#" id="forgotLink">Forgot password?</a>
        </div>

        <button class="btn-coffee" type="submit">
          Sign in <i class="bi bi-arrow-up-right"></i>
        </button>

        <p class="form-note" id="loginNote" aria-live="polite"></p>
      </form>

      <div class="divider">or continue with</div>

      <div class="social-row">
        <button class="social-btn" type="button" data-provider="Google">
          <i class="bi bi-google"></i> Google
        </button>
        <button class="social-btn" type="button" data-provider="Apple">
          <i class="bi bi-apple"></i> Apple
        </button>
      </div>

      <p class="auth-switch">New around here? <a href="register.php" id="createLink">Create an account</a></p>
    </section>

  </main>

  <script>
    (function () {
      'use strict';

      var form = document.getElementById('loginForm');
      var email = document.getElementById('loginEmail');
      var password = document.getElementById('loginPassword');
      var note = document.getElementById('loginNote');
      var toggle = document.getElementById('togglePassword');

      toggle.addEventListener('click', function () {
        var isPassword = password.type === 'password';
        password.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(isPassword));
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        toggle.innerHTML = isPassword
          ? '<i class="bi bi-eye-slash"></i>'
          : '<i class="bi bi-eye"></i>';
        password.focus();
      });

      function setNote(message, type) {
        note.textContent = message;
        note.className = 'form-note' + (type ? ' ' + type : '');
      }
      function validateEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
      }
      function clearError(input) { input.style.borderColor = ''; }
      function markError(input) { input.style.borderColor = '#a33a2a'; }

      email.addEventListener('input', function () { clearError(email); });
      password.addEventListener('input', function () { clearError(password); });

      form.addEventListener('submit', function (e) {
        var emailValue = email.value.trim();
        var passwordValue = password.value;
        var valid = true;

        if (!emailValue) {
          markError(email);
          setNote('Please enter your email address.', 'error');
          email.focus();
          valid = false;
        } else if (!validateEmail(emailValue)) {
          markError(email);
          setNote('That email address doesn\'t look quite right.', 'error');
          email.focus();
          valid = false;
        } else if (!passwordValue) {
          markError(password);
          setNote('Please enter your password.', 'error');
          password.focus();
          valid = false;
        } else if (passwordValue.length < 6) {
          markError(password);
          setNote('Your password must be at least 6 characters.', 'error');
          password.focus();
          valid = false;
        }

        if (!valid) {
          e.preventDefault();
          return;
        }

        setNote('Signing you in…', 'success');
        form.querySelector('button[type="submit"]').disabled = true;
        form.querySelector('button[type="submit"]').innerHTML =
          'Signing in… <i class="bi bi-arrow-repeat"></i>';
      });

      document.querySelectorAll('.social-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var provider = btn.getAttribute('data-provider') || 'provider';
          setNote('Demo only — connect ' + provider + ' sign-in to enable this.', '');
        });
      });

      document.getElementById('forgotLink').addEventListener('click', function (e) {
        e.preventDefault();
        setNote('Demo only — link this to your password reset flow.', '');
      });
    })();
  </script>
</body>
</html>