<?php
// ============================================================
//  register.php  —  new user registration
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';


/* ---------- If already logged in, go home ---------- */
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

/* ---------- Simple CSRF token ---------- */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Handle form submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $errors[] = 'Your session expired. Please try again.';
    }

    // Collect + sanitise
    $old['name']  = trim($_POST['name']  ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $password     = (string)($_POST['password'] ?? '');
    $confirm      = (string)($_POST['confirm']  ?? '');
    $terms        = isset($_POST['terms']);

    // Validate
    if ($old['name'] === '' || mb_strlen($old['name']) > 120) {
        $errors[] = 'Please enter your full name (max 120 characters).';
    }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s]{8,15}$/', $old['phone'])) {
        $errors[] = 'Please enter a valid phone number (8–15 digits).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Your password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Those passwords don\'t match.';
    }
    if (!$terms) {
        $errors[] = 'Please agree to the Terms of Service to continue.';
    }

    // Check email not taken
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Try signing in instead.';
        }
    }

    // Insert new user
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, phone, password_hash)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $old['name'],
                $old['email'],
                $old['phone'] !== '' ? $old['phone'] : null,
                $hash,
            ]);

            // Log them in right away
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            error_log('Register failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong on our side. Please try again in a moment.';
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
  <title>Create Account — Café Krunal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --ink:#211914; --coffee:#4a3024; --cream:#f4efe7; --paper:#fbf9f5;
      --tan:#c59b70; --muted:#83766b; --line:rgba(49,35,27,.14);
      --serif:"Playfair Display",Georgia,serif; --sans:"DM Sans",Arial,sans-serif;
    }
    *{box-sizing:border-box}
    body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--sans);
      min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
    a{color:inherit;text-decoration:none}

    .auth-shell{width:100%;max-width:1060px;background:#fffdf9;
      box-shadow:0 30px 80px rgba(32,23,17,.12);display:grid;
      grid-template-columns:1fr 1.15fr;min-height:660px;overflow:hidden;
      border:1px solid var(--line)}

    .auth-visual{position:relative;background:#211914;color:#fff;overflow:hidden;
      display:flex;flex-direction:column;justify-content:space-between;padding:40px}
    .auth-visual-bg{position:absolute;inset:0;
      background:linear-gradient(160deg,rgba(22,15,11,.9) 0%,rgba(22,15,11,.55) 55%,rgba(22,15,11,.3) 100%),
      url("https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=90") center/cover}
    .auth-visual>*{position:relative;z-index:1}

    .brand{display:inline-flex;align-items:center;gap:10px;
      font:600 1.3rem var(--serif);letter-spacing:-.04em;color:#fff}
    .brand-mark{width:34px;height:34px;display:grid;place-items:center;
      border:1px solid var(--tan);border-radius:50%;color:#e5c5a2;
      font:500 1rem var(--serif);flex:0 0 auto}

    .visual-quote{max-width:340px;margin-top:auto;padding-top:40px}
    .visual-quote .eyebrow{display:inline-flex;align-items:center;gap:10px;
      color:#e5c5a2;font-size:.64rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase}
    .visual-quote .eyebrow:before{content:"";width:27px;height:1px;background:var(--tan)}
    .visual-quote h2{margin:18px 0 14px;
      font:400 clamp(1.8rem,3.6vw,2.9rem)/1.08 var(--serif);letter-spacing:-.05em}
    .visual-quote h2 em{color:#d8b38d;font-style:normal}
    .visual-quote p{color:rgba(255,255,255,.7);font-size:.85rem;line-height:1.8;margin:0}

    .perks{list-style:none;margin:26px 0 0;padding:0;display:grid;gap:12px}
    .perks li{display:flex;align-items:flex-start;gap:10px;
      color:rgba(255,255,255,.78);font-size:.78rem;line-height:1.6}
    .perks li i{color:#d8b38d;font-size:.9rem;margin-top:1px}

    .visual-foot{margin-top:34px;padding-top:18px;
      border-top:1px solid rgba(255,255,255,.16);
      color:rgba(255,255,255,.55);font-size:.62rem;letter-spacing:.16em;text-transform:uppercase}

    .auth-form-panel{padding:46px 52px;display:flex;flex-direction:column;justify-content:center}
    .auth-kicker{display:inline-flex;align-items:center;gap:10px;color:var(--coffee);
      font-size:.64rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase}
    .auth-kicker:before{content:"";width:24px;height:1px;background:var(--tan)}
    .auth-title{margin:16px 0 8px;
      font:400 clamp(1.9rem,3.4vw,2.5rem)/1.12 var(--serif);letter-spacing:-.045em}
    .auth-sub{color:var(--muted);font-size:.85rem;line-height:1.75;margin-bottom:26px}

    .form-label{color:#6e5d50;font-size:.63rem;font-weight:700;
      letter-spacing:.12em;text-transform:uppercase;margin-bottom:7px}
    .form-control{min-height:48px;border:1px solid #ddd1c4;border-radius:0;
      background:#fffdf9;font-size:.88rem;padding:11px 14px;color:var(--ink)}
    .form-control::placeholder{color:#b1a495}
    .form-control:focus{border-color:#a77b54;
      box-shadow:0 0 0 .2rem rgba(167,123,84,.12);background:#fff}

    .field{margin-bottom:14px}
    .input-wrap{position:relative}
    .input-wrap .form-control{padding-right:46px}
    .toggle-pass{position:absolute;top:50%;right:6px;transform:translateY(-50%);
      width:38px;height:38px;display:grid;place-items:center;border:0;background:transparent;
      color:var(--muted);font-size:1rem;cursor:pointer;border-radius:50%;transition:.2s}
    .toggle-pass:hover{color:var(--coffee);background:rgba(167,123,84,.1)}

    .strength{display:flex;gap:5px;margin-top:8px}
    .strength span{flex:1;height:4px;background:#e6dcd0;transition:background .3s}
    .strength[data-level="1"] span:nth-child(1){background:#c25a45}
    .strength[data-level="2"] span:nth-child(-n+2){background:#d69a4a}
    .strength[data-level="3"] span:nth-child(-n+3){background:#b89a3c}
    .strength[data-level="4"] span{background:#5a8b57}
    .strength-hint{display:block;margin-top:6px;font-size:.68rem;
      color:var(--muted);min-height:14px}

    .check{display:inline-flex;align-items:flex-start;gap:9px;font-size:.76rem;
      color:#6e5d50;line-height:1.55;cursor:pointer;user-select:none;margin:6px 0 20px}
    .check input{width:16px;height:16px;accent-color:var(--coffee);
      cursor:pointer;margin:1px 0 0;flex:0 0 auto}
    .check a{color:var(--coffee);font-weight:700;border-bottom:1px solid var(--tan);padding-bottom:1px}

    .btn-coffee{display:inline-flex;align-items:center;justify-content:center;gap:12px;
      width:100%;padding:15px 22px;border:1px solid var(--ink);background:var(--ink);
      color:#fff;border-radius:0;font-size:.72rem;font-weight:700;letter-spacing:.13em;
      text-transform:uppercase;transition:.3s ease;cursor:pointer}
    .btn-coffee:hover{background:var(--tan);border-color:var(--tan);
      color:var(--ink);transform:translateY(-2px)}
    .btn-coffee:active{transform:translateY(0)}

    .divider{display:flex;align-items:center;gap:14px;margin:22px 0;
      color:#a89a8d;font-size:.62rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase}
    .divider:before,.divider:after{content:"";flex:1;height:1px;background:var(--line)}

    .social-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .social-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;
      padding:12px 14px;border:1px solid var(--line);background:transparent;color:var(--ink);
      font-size:.76rem;font-weight:600;letter-spacing:.04em;transition:.25s;cursor:pointer}
    .social-btn i{font-size:1rem;color:var(--coffee)}
    .social-btn:hover{background:var(--cream);border-color:#cdb9a4;transform:translateY(-2px)}

    .auth-switch{margin-top:24px;text-align:center;font-size:.8rem;color:var(--muted)}
    .auth-switch a{color:var(--coffee);font-weight:700;
      border-bottom:1px solid var(--tan);padding-bottom:1px}

    .form-note{margin-top:14px;font-size:.72rem;color:var(--muted);line-height:1.6;min-height:18px}

    @media(max-width:991px){
      .auth-shell{grid-template-columns:1fr;max-width:540px;min-height:0}
      .auth-visual{padding:30px 30px 26px;min-height:240px}
      .visual-quote{padding-top:24px}
      .visual-quote h2{font-size:clamp(1.6rem,5vw,2.1rem)}
      .perks{margin-top:18px;gap:9px}
      .visual-foot{display:none}
      .auth-form-panel{padding:40px 30px}
    }
    @media(max-width:480px){
      body{padding:0;align-items:stretch}
      .auth-shell{border:0;box-shadow:none;max-width:100%;min-height:100vh}
      .auth-visual{min-height:210px;padding:26px 22px 22px}
      .perks{display:none}
      .auth-form-panel{padding:30px 22px 40px}
      .social-row{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
  <main class="auth-shell">

    <!-- Visual panel -->
    <aside class="auth-visual">
      <div class="auth-visual-bg"></div>
      <a class="brand" href="index.php"><span class="brand-mark">K</span> café krunal<span style="color:#b58a62">.</span></a>
      <div class="visual-quote">
        <div class="eyebrow">Your seat at the table</div>
        <h2>Join the<br><em>coffee club.</em></h2>
        <p>Create an account and make every visit a little easier — and a little sweeter.</p>
        <ul class="perks">
          <li><i class="bi bi-bookmark-heart"></i> Save your favourite orders in one place</li>
          <li><i class="bi bi-calendar2-check"></i> Book tables faster, with your details remembered</li>
          <li><i class="bi bi-cup-hot"></i> Collect little rewards with every cup</li>
        </ul>
      </div>
      <div class="visual-foot">Coffee · Conversation · Community</div>
    </aside>

    <!-- Form panel -->
    <section class="auth-form-panel">
      <div class="auth-kicker">A warm welcome</div>
      <h1 class="auth-title">Create your account.</h1>
      <p class="auth-sub">A few details and you're in. We'll keep the good coffee waiting.</p>

      <?php if ($errors): ?>
        <div class="alert alert-danger py-2 small mb-3">
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="register.php" novalidate>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="field">
          <label class="form-label" for="regName">Full name</label>
          <input class="form-control" type="text" id="regName" name="name"
                 value="<?= e($old['name']) ?>" placeholder="Your full name"
                 autocomplete="name" maxlength="120" required>
        </div>

        <div class="field">
          <label class="form-label" for="regEmail">Email address</label>
          <input class="form-control" type="email" id="regEmail" name="email"
                 value="<?= e($old['email']) ?>" placeholder="you@example.com"
                 autocomplete="email" maxlength="190" required>
        </div>

        <div class="field">
          <label class="form-label" for="regPhone">Phone number <span style="color:#a89a8d;font-weight:600;letter-spacing:0;text-transform:none;">(optional)</span></label>
          <input class="form-control" type="tel" id="regPhone" name="phone"
                 value="<?= e($old['phone']) ?>" placeholder="+91 00000 00000"
                 autocomplete="tel" maxlength="15">
        </div>

        <div class="field">
          <label class="form-label" for="regPassword">Password</label>
          <div class="input-wrap">
            <input class="form-control" type="password" id="regPassword" name="password"
                   placeholder="Create a password" autocomplete="new-password"
                   minlength="8" required>
            <button class="toggle-pass" type="button" id="togglePassword"
                    aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
          </div>
          <div class="strength" id="strengthBar" data-level="0" aria-hidden="true">
            <span></span><span></span><span></span><span></span>
          </div>
          <span class="strength-hint" id="strengthHint">Use 8+ characters with a mix of letters, numbers &amp; symbols.</span>
        </div>

        <div class="field">
          <label class="form-label" for="regConfirm">Confirm password</label>
          <div class="input-wrap">
            <input class="form-control" type="password" id="regConfirm" name="confirm"
                   placeholder="Re-enter your password" autocomplete="new-password"
                   minlength="8" required>
          </div>
        </div>

        <label class="check">
          <input type="checkbox" name="terms" id="agreeTerms" required>
          <span>I agree to the <a href="#" data-demo="terms">Terms of Service</a> and <a href="#" data-demo="privacy">Privacy Policy</a>.</span>
        </label>

        <button class="btn-coffee" type="submit">Create account <i class="bi bi-arrow-up-right"></i></button>
      </form>

      <div class="divider">or sign up with</div>
      <div class="social-row">
        <button class="social-btn" type="button" data-provider="Google"><i class="bi bi-google"></i> Google</button>
        <button class="social-btn" type="button" data-provider="Apple"><i class="bi bi-apple"></i> Apple</button>
      </div>

      <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
    </section>
  </main>

  <script>
    // Show / hide password
    document.getElementById('togglePassword').addEventListener('click', function () {
      var p = document.getElementById('regPassword');
      var show = p.type === 'password';
      p.type = show ? 'text' : 'password';
      this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
      this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      p.focus();
    });

    // Password strength meter
    (function () {
      var p = document.getElementById('regPassword');
      var bar = document.getElementById('strengthBar');
      var hint = document.getElementById('strengthHint');
      var labels = {
        0:'Use 8+ characters with a mix of letters, numbers & symbols.',
        1:'That password is a little weak — try adding numbers or symbols.',
        2:'Getting there — mix in uppercase letters and symbols.',
        3:'Good password — a symbol or extra length would make it great.',
        4:'Excellent password — strong and ready to go.'
      };
      p.addEventListener('input', function () {
        var v = p.value, s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/[0-9]/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        var lvl = s <= 1 ? 1 : (s === 2 ? 2 : (s === 3 ? 3 : 4));
        bar.setAttribute('data-level', String(lvl));
        hint.textContent = labels[lvl] || labels[0];
        hint.style.color = lvl >= 3 ? '#3d6b45' : (lvl === 1 ? '#a33a2a' : 'var(--muted)');
      });
    })();

    // Demo links
    document.querySelectorAll('[data-demo]').forEach(function (a) {
      a.addEventListener('click', function (e) { e.preventDefault(); });
    });
    document.querySelectorAll('.social-btn').forEach(function (b) {
      b.addEventListener('click', function (e) { e.preventDefault(); });
    });
  </script>
</body>
</html>