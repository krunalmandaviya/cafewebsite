<?php
// ============================================================
//  index.php  —  homepage
//  Dynamic: hero, story, marquee, menu, feature, visit
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

/* ---------- Escape helper ---------- */
if (!function_exists('e')) {
    function e(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/* ---------- Current user ---------- */
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch() ?: null;
}

/* ============================================================
 |  HERO
 * ============================================================ */
$stmt = $pdo->query("
    SELECT title_line1, title_line2, subtitle, image_path
    FROM hero_section
    WHERE is_active = 1
    ORDER BY updated_at DESC
    LIMIT 1
");
$hero = $stmt->fetch() ?: [];

$titleLine1 = trim((string)($hero['title_line1'] ?? '')) ?: 'Good coffee.';
$titleLine2 = trim((string)($hero['title_line2'] ?? '')) ?: 'Better moments.';
$subtitle   = trim((string)($hero['subtitle']    ?? '')) ?: 'A neighbourhood café for slow mornings, thoughtful coffee, freshly made plates and conversations that last a little longer.';

$heroBgPath = trim((string)($hero['image_path'] ?? ''));
$heroBgUrl  = 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=2200&q=90';
if ($heroBgPath !== '') {
    $filename = basename(ltrim($heroBgPath, '/'));
    $relative = 'admin/uploads/hero/' . $filename;
    if (is_file(__DIR__ . '/' . $relative)) {
        $heroBgUrl = '/cafe/' . $relative;
    }
}

/* ============================================================
 |  STORY
 * ============================================================ */
$stmt = $pdo->query("
    SELECT eyebrow, title_line1, title_line2,
           paragraph1, paragraph2,
           image_path, stamp_text,
           button_text, button_link
    FROM story_section
    WHERE is_active = 1
    ORDER BY updated_at DESC
    LIMIT 1
");
$story = $stmt->fetch() ?: [];

$storyEyebrow = trim((string)($story['eyebrow']     ?? '')) ?: 'A place to slow down';
$storyTitle1  = trim((string)($story['title_line1'] ?? '')) ?: 'Not just a café.';
$storyTitle2  = trim((string)($story['title_line2'] ?? '')) ?: 'A feeling.';
$storyPara1   = trim((string)($story['paragraph1']  ?? '')) ?: 'We believe the best moments are often the simplest: the first sip of a really good coffee, a favourite pastry shared, or a familiar face across the table. Café Krunal brings those little moments together in one warm, welcoming space.';
$storyPara2   = trim((string)($story['paragraph2']  ?? '')) ?: 'Thoughtfully sourced beans, fresh ingredients and genuine hospitality are at the heart of everything we do.';
$storyStamp   = trim((string)($story['stamp_text']  ?? '')) ?: 'Made with care & good coffee';
$storyBtnText = trim((string)($story['button_text'] ?? '')) ?: 'Come say hello';
$storyBtnLink = trim((string)($story['button_link'] ?? '')) ?: '#visit';
$storyImgPath = trim((string)($story['image_path']  ?? ''));

$storyImgUrl = 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1100&q=85';
if ($storyImgPath !== '') {
    if (preg_match('#^https?://#i', $storyImgPath)) {
        $storyImgUrl = $storyImgPath;
    } else {
        $filename = basename(ltrim($storyImgPath, '/'));
        $relative = 'admin/uploads/story/' . $filename;
        if (is_file(__DIR__ . '/' . $relative)) {
            $storyImgUrl = '/cafe/' . $relative;
        }
    }
}
$stampLines = preg_split('/\s*&\s*|\s*,\s*/', $storyStamp, 3);
$stampHtml  = implode('<br>', array_map('e', $stampLines));

/* ============================================================
 |  MARQUEE
 * ============================================================ */
$stmt = $pdo->query("
    SELECT items FROM marquee_section
    WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1
");
$marqueeRow = $stmt->fetch() ?: [];

$marqueeRaw = trim((string)($marqueeRow['items'] ?? ''));
if ($marqueeRaw === '') {
    $marqueeRaw = 'Freshly brewed|Thoughtfully made|Always welcoming|Stay for another cup';
}
$marqueeItems = array_values(array_filter(
    array_map('trim', explode('|', $marqueeRaw)),
    fn($v) => $v !== ''
));
$marqueeHtml = '';
foreach ($marqueeItems as $item) {
    $marqueeHtml .= e($item) . ' <span>✳</span> ';
}

/* ============================================================
 |  MENU — categories + items
 * ============================================================ */
$categories = $pdo->query("
    SELECT id, slug, label FROM menu_categories
    ORDER BY sort_order ASC, id ASC
")->fetchAll() ?: [];

$items = $pdo->query("
    SELECT id, category_id, name, description, price, image_path, sort_order
    FROM menu_items
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll() ?: [];

$itemsByCat = [];
foreach ($items as $it) {
    $itemsByCat[(int)$it['category_id']][] = $it;
}
$visibleCategories = array_values(array_filter(
    $categories,
    fn($c) => !empty($itemsByCat[(int)$c['id']])
));

function menu_img_url(string $path, string $fallback): string {
    $path = trim($path);
    if ($path === '') return $fallback;
    if (preg_match('#^https?://#i', $path)) return $path;
    $filename = basename(ltrim($path, '/'));
    $relative = 'admin/uploads/menu/' . $filename;
    if (is_file(__DIR__ . '/' . $relative)) {
        return '/cafe/' . $relative;
    }
    return $fallback;
}

/* ============================================================
 |  FEATURE
 * ============================================================ */
$stmt = $pdo->query("
    SELECT eyebrow, title_line1, title_line2, title_line2_color,
           description, cta_text, cta_link, bg_image_path
    FROM feature_section
    WHERE is_active = 1
    ORDER BY updated_at DESC
    LIMIT 1
");
$feature = $stmt->fetch() ?: [];

$featureEyebrow   = trim((string)($feature['eyebrow']          ?? '')) ?: 'The art of a good cup';
$featureTitle1    = trim((string)($feature['title_line1']      ?? '')) ?: 'Good things take';
$featureTitle2    = trim((string)($feature['title_line2']      ?? '')) ?: 'a little time.';
$featureTitle2Col = trim((string)($feature['title_line2_color'] ?? '')) ?: '#d8b38d';
$featureDesc      = trim((string)($feature['description']      ?? '')) ?: 'Carefully selected beans. Thoughtful preparation. A cup made just the way it should be — never rushed, always worth it.';
$featureCtaText   = trim((string)($feature['cta_text']         ?? '')) ?: 'Find your favourite';
$featureCtaLink   = trim((string)($feature['cta_link']         ?? '')) ?: '#menu';
$featureBgPath    = trim((string)($feature['bg_image_path']    ?? ''));

$featureBgUrl = 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=2000&q=85';
if ($featureBgPath !== '') {
    if (preg_match('#^https?://#i', $featureBgPath)) {
        $featureBgUrl = $featureBgPath;
    } else {
        $filename = basename(ltrim($featureBgPath, '/'));
        $relative = 'admin/uploads/feature/' . $filename;
        if (is_file(__DIR__ . '/' . $relative)) {
            $featureBgUrl = '/cafe/' . $relative;
        }
    }
}

if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $featureTitle2Col)) {
    $featureTitle2Col = '#d8b38d';
}

/* ============================================================
 |  VISIT SECTION
 * ============================================================ */
$stmt = $pdo->query("
    SELECT eyebrow, title_line1, title_line2, section_copy,
           findus_heading, findus_line1, findus_line2,
           hours_heading, hours_days, hours_time, hours_note,
           contact_heading, contact_phone, contact_email,
           form_title, form_note
    FROM visit_section
    WHERE is_active = 1
    ORDER BY updated_at DESC
    LIMIT 1
");
$visit = $stmt->fetch() ?: [];

$visitEyebrow     = trim((string)($visit['eyebrow']         ?? '')) ?: 'Your next favourite corner';
$visitTitle1      = trim((string)($visit['title_line1']     ?? '')) ?: "Let's make";
$visitTitle2      = trim((string)($visit['title_line2']     ?? '')) ?: 'some time.';
$visitCopy        = trim((string)($visit['section_copy']    ?? '')) ?: 'Drop by for your daily coffee, bring a friend for brunch, or make a reservation for a slow afternoon.';
$visitFindHeading = trim((string)($visit['findus_heading']  ?? '')) ?: 'Find us';
$visitFindLine1   = trim((string)($visit['findus_line1']    ?? '')) ?: 'Your café address goes here';
$visitFindLine2   = trim((string)($visit['findus_line2']    ?? '')) ?: 'Surat, Gujarat, India';
$visitHoursHead   = trim((string)($visit['hours_heading']   ?? '')) ?: 'Opening hours';
$visitHoursDays   = trim((string)($visit['hours_days']      ?? '')) ?: 'Monday – Sunday';
$visitHoursTime   = trim((string)($visit['hours_time']      ?? '')) ?: '9:00 AM – 10:00 PM';
$visitHoursNote   = trim((string)($visit['hours_note']      ?? ''));
$visitContactHead = trim((string)($visit['contact_heading'] ?? '')) ?: 'Say hello';
$visitPhone       = trim((string)($visit['contact_phone']   ?? '')) ?: '+91 00000 00000';
$visitEmail       = trim((string)($visit['contact_email']   ?? '')) ?: 'hello@cafekrunal.com';
$visitFormTitle   = trim((string)($visit['form_title']      ?? '')) ?: 'Save yourself a seat.';
$visitFormNote    = trim((string)($visit['form_note']       ?? '')) ?: 'Demo form: connect WhatsApp, email or a booking backend to receive real reservations.';

/* Time + Guests dropdowns */
$visitOptions = $pdo->query("
    SELECT field, label
    FROM visit_form_options
    WHERE is_active = 1
    ORDER BY field ASC, sort_order ASC, id ASC
")->fetchAll() ?: [];

$timeOptions  = [];
$guestOptions = [];
foreach ($visitOptions as $opt) {
    if ($opt['field'] === 'time')   $timeOptions[]  = $opt['label'];
    if ($opt['field'] === 'guests') $guestOptions[] = $opt['label'];
}

/* Fallbacks if the table has no rows yet */
if (empty($timeOptions)) {
    $timeOptions = ['09:00 AM','10:00 AM','11:00 AM','12:00 PM','01:00 PM',
                    '02:00 PM','03:00 PM','04:00 PM','05:00 PM','06:00 PM',
                    '07:00 PM','08:00 PM','09:00 PM'];
}
if (empty($guestOptions)) {
    $guestOptions = ['1','2','3','4','5','6'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Discover Café Krunal — specialty coffee, handcrafted plates and slow moments in a warm, welcoming space.">
  <meta name="theme-color" content="#211914">
  <title>Café Krunal — Coffee, Comfort & Good Company</title>

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
    html { scroll-behavior: smooth; scroll-padding-top: 80px; }
    body { margin: 0; background: var(--paper); color: var(--ink); font-family: var(--sans); overflow-x: hidden; -webkit-font-smoothing: antialiased; }
    a { color: inherit; text-decoration: none; }
    img { display: block; max-width: 100%; }
    .eyebrow { display: inline-flex; align-items: center; gap: 10px; color: var(--coffee); font-size: .68rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
    .eyebrow:before { content: ""; width: 27px; height: 1px; background: var(--tan); }
    .section-pad { padding: 112px 0; }
    .section-title { font: 400 clamp(2.2rem, 5.5vw, 5.2rem)/1.08 var(--serif); letter-spacing: -.055em; }
    .section-copy { color: var(--muted); line-height: 1.9; font-size: .96rem; }
    .btn-coffee { display: inline-flex; align-items: center; justify-content: center; gap: 13px; padding: 15px 23px; border: 1px solid var(--ink); background: var(--ink); color: #fff; border-radius: 0; font-size: .72rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; transition: .3s ease; }
    .btn-coffee:hover { background: var(--tan); border-color: var(--tan); color: var(--ink); transform: translateY(-3px); }
    .btn-outline-coffee { background: transparent; color: var(--ink); }
    .btn-outline-coffee:hover { color: #fff; }
    .navbar { padding: 18px 0; background: rgba(251,249,245,.96); backdrop-filter: blur(16px); border-bottom: 1px solid transparent; transition: .3s; }
    .navbar.scrolled { padding: 12px 0; border-color: var(--line); box-shadow: 0 8px 30px rgba(32,23,17,.04); }
    .brand { display: flex; align-items: center; gap: 10px; font: 600 1.35rem var(--serif); letter-spacing: -.04em; }
    .brand-mark { width: 32px; height: 32px; display: grid; place-items: center; border: 1px solid var(--tan); border-radius: 50%; color: var(--coffee); font: 500 1rem var(--serif); }
    .navbar .nav-link { margin: 0 10px; color: #55483f; font-size: .72rem; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; }
    .navbar .nav-link:hover { color: #b17d4d; }
    .navbar-toggler { border: 0; box-shadow: none !important; font-size: 1.5rem; }

    .hero { min-height: 760px; height: min(890px, 100svh); position: relative; display: flex; align-items: center; overflow: hidden; background: #211914; color: white; }
    .hero-bg {
      position: absolute; inset: 0; width: 100%; height: 100%;
      background-size: cover; background-position: center; background-repeat: no-repeat;
      transform: scale(1.04); z-index: 0;
    }
    .hero-content { position: relative; z-index: 1; padding-top: 55px; }
    .hero .eyebrow { color: #e5c5a2; }
    .hero h1 { max-width: 820px; margin: 25px 0; font: 400 clamp(3.2rem, 9vw, 8.6rem)/.95 var(--serif); letter-spacing: -.06em; }
    .hero h1 em { color: #d8b38d; font-weight: 400; }
    .hero-copy { max-width: 430px; color: rgba(255,255,255,.76); line-height: 1.8; font-size: .98rem; }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 34px; }
    .hero .btn-coffee { background: var(--cream); color: var(--ink); border-color: var(--cream); }
    .hero .btn-coffee:hover { background: var(--tan); border-color: var(--tan); }
    .hero .btn-outline-coffee { color: white; border-color: rgba(255,255,255,.55); background: transparent; }
    .hero .btn-outline-coffee:hover { background: white; color: var(--ink); }
    .hero-note { position: absolute; z-index: 1; right: 5%; bottom: 55px; color: rgba(255,255,255,.72); font-size: .65rem; letter-spacing: .2em; text-transform: uppercase; writing-mode: vertical-rl; }
    .hero-scroll { position: absolute; z-index: 1; bottom: 42px; left: max(24px, calc((100vw - 1320px)/2)); display: flex; align-items: center; gap: 13px; color: rgba(255,255,255,.7); font-size: .63rem; letter-spacing: .18em; text-transform: uppercase; }
    .hero-scroll:before { content: ""; width: 42px; height: 1px; background: #d8b38d; }

    .hero-welcome {
      display: inline-flex; align-items: center; gap: 12px;
      margin-bottom: 22px; margin-top: 4%;
      padding: 10px 18px 10px 12px;
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .22);
      border-radius: 999px;
      backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
      color: #f4efe7; font-size: .8rem; font-weight: 500; letter-spacing: .02em;
      animation: welcomeFade .8s ease both;
    }
    .hero-welcome .w-avatar { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 50%; background: var(--tan); color: var(--ink); font: 600 .82rem var(--serif); flex: 0 0 auto; }
    .hero-welcome strong { color: #fff; font-weight: 700; }
    .hero-welcome .w-dot { width: 6px; height: 6px; border-radius: 50%; background: #8fbf8a; box-shadow: 0 0 0 3px rgba(143, 191, 138, .22); flex: 0 0 auto; }
    @keyframes welcomeFade { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

    .intro { background: var(--cream); }
    .intro-image-wrap { position: relative; padding: 0 35px 35px 0; }
    .intro-image-wrap:after { content: ""; position: absolute; right: 0; bottom: 0; width: 65%; height: 65%; border: 1px solid #c9a783; z-index: 0; }
    .intro-image { position: relative; z-index: 1; width: 100%; height: 540px; object-fit: cover; }
    .round-stamp { position: absolute; z-index: 2; right: -15px; top: 35px; width: 115px; height: 115px; display: grid; place-items: center; border-radius: 50%; background: var(--coffee); color: #f5e8d9; text-align: center; font: 1rem/1.3 var(--serif); transform: rotate(9deg); padding: 8px; }
    .intro-copy { max-width: 480px; }
    .intro-copy .section-title { margin: 20px 0 25px; }
    .text-link { display: inline-flex; gap: 14px; align-items: center; margin-top: 22px; padding-bottom: 8px; border-bottom: 1px solid var(--tan); font-size: .72rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }

    .marquee { overflow: hidden; padding: 20px 0; background: var(--coffee); color: #f3e7d9; white-space: nowrap; }
    .marquee-track { display: inline-flex; align-items: center; gap: 35px; min-width: max-content; animation: marquee 32s linear infinite; font: 400 1.4rem var(--serif); }
    .marquee-track span { color: #d4ad86; font-size: 1rem; }
    @keyframes marquee { to { transform: translateX(-50%); } }

    .menu-section { background: var(--paper); }
    .menu-tabs { display: flex; flex-wrap: wrap; justify-content: center; gap: 9px; margin: 30px 0 40px; }
    .menu-tab { padding: 11px 18px; border: 1px solid var(--line); background: transparent; color: var(--muted); font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; transition: .25s; white-space: nowrap; }
    .menu-tab.active, .menu-tab:hover { background: var(--ink); color: white; border-color: var(--ink); }
    .menu-card { display: flex; gap: 19px; align-items: center; padding: 18px 0; border-bottom: 1px solid var(--line); height: 100%; }
    .menu-card img { width: 112px; height: 112px; flex: 0 0 112px; object-fit: cover; }
    .menu-card h3 { margin: 0 0 6px; font: 500 1.25rem var(--serif); }
    .menu-card p { margin: 0; color: var(--muted); font-size: .78rem; line-height: 1.6; }
    .menu-price { margin-left: auto; padding-left: 10px; color: var(--coffee); font: 500 1rem var(--serif); white-space: nowrap; }
    .menu-item { transition: opacity .25s, transform .25s; }
    .menu-item.hide { display: none; }
    .menu-footnote { margin-top: 30px; color: var(--muted); font-size: .75rem; text-align: center; }

    .feature { position: relative; min-height: 620px; display: flex; align-items: center; color: white; background: #241912; overflow: hidden; }
    .feature-bg { position: absolute; inset: 0; background-size: cover; background-position: center; background-repeat: no-repeat; }
    .feature-content { position: relative; z-index: 1; padding: 100px 0; }
    .feature .eyebrow { color: #e3c19e; }
    .feature h2 { max-width: 650px; margin: 22px 0; font: 400 clamp(2.4rem, 6vw, 6rem)/1.02 var(--serif); letter-spacing: -.06em; }
    .feature p { max-width: 430px; color: rgba(255,255,255,.72); line-height: 1.9; }

    .gallery { background: var(--cream); }
    .gallery-grid { display: grid; grid-template-columns: 1.1fr .9fr 1fr; grid-template-rows: 250px 250px; gap: 15px; margin-top: 55px; }
    .gallery-grid a { position: relative; overflow: hidden; display: block; }
    .gallery-grid a:first-child { grid-row: span 2; }
    .gallery-grid a:nth-child(4) { grid-column: span 2; }
    .gallery-grid img { width: 100%; height: 100%; object-fit: cover; transition: transform .8s cubic-bezier(.2,.7,.2,1); }
    .gallery-grid a:hover img { transform: scale(1.07); }
    .gallery-grid a:after { content: "VIEW MOMENT  ↗"; position: absolute; right: 18px; bottom: 18px; color: white; font-size: .62rem; letter-spacing: .14em; opacity: 0; transition: .3s; }
    .gallery-grid a:hover:after { opacity: 1; }
    .values { background: var(--paper); }
    .value-card { padding: 32px 25px; border-top: 1px solid var(--tan); height: 100%; }
    .value-card i { color: #a77b54; font-size: 1.5rem; }
    .value-card h3 { margin: 25px 0 12px; font: 500 1.55rem var(--serif); }
    .value-card p { margin: 0; color: var(--muted); font-size: .85rem; line-height: 1.8; }
    .quote-section { background: #e9dfd2; text-align: center; }
    .quote-mark { color: #b38b65; font: 5rem/.8 var(--serif); }
    .quote-text { max-width: 850px; margin: 24px auto; font: 400 clamp(1.8rem, 4vw, 3.7rem)/1.25 var(--serif); letter-spacing: -.04em; }
    .quote-author { color: var(--muted); font-size: .68rem; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; }
    .visit { background: var(--ink); color: white; }
    .visit .eyebrow { color: #d7b38f; }
    .visit .section-title { color: white; }
    .visit .section-copy { color: rgba(255,255,255,.62); }
    .visit-detail { padding: 22px 0; border-bottom: 1px solid rgba(255,255,255,.16); }
    .visit-detail h3 { margin-bottom: 8px; color: #d9b590; font-size: .65rem; font-weight: 700; letter-spacing: .17em; text-transform: uppercase; }
    .visit-detail p { margin: 0; color: rgba(255,255,255,.78); font-size: .9rem; line-height: 1.8; }
    .booking-form { padding: 38px; background: #f6f1e9; color: var(--ink); }
    .booking-form h3 { margin-bottom: 25px; font: 400 2.2rem var(--serif); }
    .form-label { color: #6e5d50; font-size: .65rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    .form-control, .form-select { min-height: 48px; border: 1px solid #ddd1c4; border-radius: 0; background: #fffdf9; font-size: .85rem; }
    .form-control:focus, .form-select:focus { border-color: #a77b54; box-shadow: 0 0 0 .2rem rgba(167,123,84,.12); }
    textarea.form-control { min-height: 100px; }
    #formAlert.alert { border-radius: 0; font-size: .82rem; padding: 14px 16px; margin-bottom: 14px; }
    #bookingSubmit:disabled { opacity: .65; cursor: wait; }

    .footer { padding: 65px 0 25px; background: #17110d; color: white; }
    .footer .brand { color: white; }
    .footer p, .footer a { color: rgba(255,255,255,.58); font-size: .82rem; line-height: 1.9; }
    .footer a:hover { color: #d9b590; }
    .footer h4 { margin-bottom: 20px; color: #d9b590; font-size: .68rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
    .footer-bottom { display: flex; justify-content: space-between; gap: 20px; margin-top: 45px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,.13); }
    .footer-bottom small { color: rgba(255,255,255,.4); font-size: .68rem; }
    .socials { display: flex; gap: 15px; }
    .socials a { font-size: 1.1rem; }
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity .8s ease, transform .8s cubic-bezier(.2,.7,.2,1); }
    .reveal.visible { opacity: 1; transform: translateY(0); }
    .to-top { position: fixed; right: 22px; bottom: 22px; z-index: 20; width: 44px; height: 44px; display: grid; place-items: center; background: var(--tan); color: var(--ink); border: 0; opacity: 0; pointer-events: none; transition: .3s; }
    .to-top.show { opacity: 1; pointer-events: auto; }

    .nav-auth-item { display: flex; align-items: center; gap: 8px; margin-left: 12px; list-style: none; }
    .nav-user { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px 6px 6px; border: 1px solid var(--line); border-radius: 999px; background: #fff; font-size: .7rem; font-weight: 600; color: var(--ink); white-space: nowrap; line-height: 1; }
    .nav-user .avatar { width: 26px; height: 26px; display: grid; place-items: center; border-radius: 50%; background: var(--coffee); color: #fff; font: 600 .7rem var(--serif); flex: 0 0 auto; }
    .nav-user .name { max-width: 110px; overflow: hidden; text-overflow: ellipsis; }
    .nav-logout-form { display: inline-block; margin: 0; padding: 0; }
    .nav-logout { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; border: 1px solid #d9a99c; background: #fff; color: #a33a2a; font-size: .66rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; border-radius: 0; cursor: pointer; line-height: 1; white-space: nowrap; transition: .25s; }
    .nav-logout:hover { background: #fbebe7; border-color: #a33a2a; color: #7d2c1f; }
    .nav-logout i { font-size: .85rem; line-height: 1; }

    @media (max-width: 991px) {
      .navbar-collapse { padding: 18px 0 8px; }
      .navbar .nav-link { margin: 7px 0; }
      .hero { min-height: 680px; height: 88svh; }
      .section-pad { padding: 80px 0; }
      .intro-image { height: 440px; }
      .gallery-grid { grid-template-rows: 210px 210px; }
      .nav-auth-item { flex-direction: column; align-items: stretch; margin: 12px 0 0; gap: 8px; }
      .nav-user { justify-content: center; width: 100%; }
      .nav-logout-form { display: block; width: 100%; }
      .nav-logout { justify-content: center; width: 100%; padding: 12px; }
    }

    @media (max-width: 767px) {
      html { scroll-padding-top: 70px; }
      .navbar { padding: 12px 0; }
      .navbar.scrolled { padding: 9px 0; }
      .brand { font-size: 1.1rem; }
      .brand-mark { width: 28px; height: 28px; font-size: .9rem; }
      .navbar-collapse { padding: 14px 0 6px; }
      .navbar-nav { display: grid !important; grid-template-columns: 1fr 1fr; gap: 8px; margin: 0 !important; }
      .navbar-nav .nav-item { margin: 0; }
      .navbar-nav .nav-link { display: flex; align-items: center; justify-content: center; margin: 0; padding: 13px 8px; border: 1px solid var(--line); background: var(--paper); font-size: .64rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; text-align: center; transition: .25s; }
      .navbar-nav .nav-link:active, .navbar-nav .nav-link:hover { background: var(--ink); color: #fff; border-color: var(--ink); }
      .navbar-nav .nav-item:last-child { grid-column: 1 / -1; }
      .navbar-nav .btn-coffee { width: 100%; margin: 4px 0 0; padding: 14px 12px; font-size: .66rem; letter-spacing: .12em; background: var(--tan); color: var(--ink); border-color: var(--tan); }
      .navbar-nav .btn-coffee:hover { background: var(--ink); color: #fff; border-color: var(--ink); }
      .navbar-nav .nav-auth-item { grid-column: 1 / -1; }

      .hero { min-height: 100svh; height: auto; padding: 130px 0 80px; }
      .hero-bg { background-position: 60% center; }
      .hero-content { padding-top: 0; }
      .hero h1 { font-size: clamp(2.8rem, 14vw, 4.6rem); line-height: .98; margin: 20px 0; }
      .hero-copy { font-size: .92rem; max-width: 100%; }
      .hero-actions { flex-direction: column; gap: 10px; margin-top: 28px; }
      .hero-actions .btn-coffee { width: 100%; justify-content: center; }
      .hero-note { display: none; }
      .hero-scroll { bottom: 20px; left: 24px; font-size: .58rem; }
      .hero-welcome { font-size: .74rem; padding: 8px 14px 8px 8px; gap: 10px; max-width: 100%; flex-wrap: wrap; border-radius: 14px; }
      .hero-welcome .w-avatar { width: 26px; height: 26px; font-size: .74rem; }

      .section-pad { padding: 64px 0; }
      .section-title { font-size: clamp(1.9rem, 8vw, 2.6rem); line-height: 1.08; }
      .eyebrow { font-size: .6rem; }
      .section-copy { font-size: .88rem; line-height: 1.8; }

      .intro-image-wrap { margin-top: 32px; padding: 0 18px 18px 0; }
      .intro-image { height: 340px; }
      .round-stamp { right: -6px; top: 20px; width: 82px; height: 82px; font-size: .75rem; }
      .intro-copy { text-align: left; }
      .intro-copy .section-title { margin: 16px 0 20px; }
      .text-link { font-size: .68rem; }

      .marquee { padding: 16px 0; }
      .marquee-track { font-size: 1.1rem; gap: 24px; }

      .menu-tabs { flex-wrap: nowrap; justify-content: space-between; gap: 4px; margin: 22px 0 30px; overflow-x: visible; }
      .menu-tab { flex: 1 1 auto; min-width: 0; padding: 9px 4px; font-size: .58rem; letter-spacing: .03em; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .menu-card { gap: 14px; padding: 14px 0; align-items: flex-start; }
      .menu-card img { width: 76px; height: 76px; flex-basis: 76px; }
      .menu-card h3 { font-size: 1rem; margin-bottom: 4px; }
      .menu-card p { font-size: .7rem; line-height: 1.5; }
      .menu-price { font-size: .85rem; padding-left: 8px; }
      .menu-footnote { font-size: .68rem; }

      .feature { min-height: 480px; }
      .feature-content { padding: 70px 0; }
      .feature h2 { font-size: clamp(2rem, 9vw, 3rem); }
      .feature p { font-size: .88rem; }
      .feature .btn-coffee { width: 100%; justify-content: center; }

      .gallery-grid { grid-template-columns: 1fr 1fr; grid-template-rows: 200px 140px 140px; gap: 8px; margin-top: 36px; }
      .gallery-grid a:first-child { grid-row: span 2; }
      .gallery-grid a:nth-child(4) { grid-column: span 2; }
      .gallery-grid a:after { display: none; }

      .values .row { flex-wrap: nowrap; gap: 6px !important; margin-left: 0; margin-right: 0; }
      .values .row > [class*="col-"] { flex: 1 1 0; max-width: 33.333%; padding-left: 3px; padding-right: 3px; }
      .value-card { padding: 18px 8px 16px; text-align: center; height: 100%; }
      .value-card i { font-size: 1.1rem; }
      .value-card h3 { margin: 12px 0 8px; font-size: .82rem; line-height: 1.2; letter-spacing: -.01em; }
      .value-card p { font-size: .58rem; line-height: 1.45; }

      .quote-text { font-size: clamp(1.5rem, 6vw, 2rem); line-height: 1.3; }
      .quote-mark { font-size: 3.5rem; }
      .quote-author { font-size: .6rem; }

      .visit .section-title { font-size: clamp(2rem, 8vw, 2.8rem); }
      .visit-detail { padding: 18px 0; }
      .visit-detail p { font-size: .85rem; }
      .booking-form { padding: 24px 18px; margin-top: 30px; }
      .booking-form h3 { font-size: 1.6rem; margin-bottom: 20px; }
      .form-control, .form-select { min-height: 46px; font-size: .82rem; }
      .booking-form .btn-coffee { width: 100%; justify-content: center; }

      .footer { padding: 50px 0 20px; }
      .footer .brand { font-size: 1.1rem; }
      .footer h4 { margin-bottom: 12px; }
      .footer-bottom { flex-direction: column; gap: 8px; margin-top: 32px; text-align: left; }
      .socials { gap: 18px; }
      .to-top { right: 16px; bottom: 16px; width: 40px; height: 40px; }
      .reveal { transform: translateY(18px); }
    }

    @media (max-width: 400px) {
      .hero h1 { font-size: clamp(2.4rem, 13vw, 3.6rem); }
      .menu-card img { width: 66px; height: 66px; flex-basis: 66px; }
      .menu-card h3 { font-size: .92rem; }
      .menu-card p { font-size: .66rem; }
      .gallery-grid { grid-template-rows: 170px 120px 120px; }
      .intro-image { height: 290px; }
      .menu-tab { font-size: .52rem; padding: 8px 3px; }
      .value-card { padding: 14px 6px 14px; }
      .value-card h3 { font-size: .74rem; }
      .value-card p { font-size: .52rem; line-height: 1.4; }
      .value-card i { font-size: .95rem; }
      .navbar-nav .nav-link { font-size: .58rem; padding: 11px 6px; }
      .navbar-nav .btn-coffee { font-size: .6rem; padding: 12px 10px; }
    }

    @media (prefers-reduced-motion: reduce) {
      *, *:before, *:after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
      .reveal { opacity: 1; transform: none; }
      .hero-welcome { animation: none; }
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
      <a class="brand" href="#home" aria-label="Café Krunal home"><span class="brand-mark">K</span> café krunal<span style="color:#b58a62">.</span></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="#story">Our Story</a></li>
          <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
          <li class="nav-item"><a class="nav-link" href="#gallery">The Space</a></li>
          <li class="nav-item"><a class="nav-link" href="#visit">Find Us</a></li>

          <?php if ($currentUser): ?>
            <li class="nav-item nav-auth-item">
              <span class="nav-user" title="<?= e($currentUser['email']) ?>">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($currentUser['full_name'], 0, 1))) ?></span>
                <span class="name"><?= e(explode(' ', $currentUser['full_name'])[0]) ?></span>
              </span>
              <form method="post" action="user/logout.php" class="nav-logout-form">
                <button type="submit" class="nav-logout" title="Sign out">
                  <i class="bi bi-box-arrow-right"></i> Logout
                </button>
              </form>
            </li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="auth/login.php">Sign In</a></li>
            <li class="nav-item ms-lg-3"><a class="btn-coffee py-3 px-4" href="#visit">Book a Table <i class="bi bi-arrow-up-right"></i></a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <header class="hero" id="home">
    <div class="hero-bg"
         style="background-image:
                  linear-gradient(90deg, rgba(22,15,11,.82) 0%, rgba(22,15,11,.58) 43%, rgba(22,15,11,.12) 100%),
                  url('<?= e($heroBgUrl) ?>');"
         aria-hidden="true"></div>

    <div class="container hero-content">
      <?php if ($currentUser): ?>
        <div class="hero-welcome reveal" role="status">
          <span class="w-dot" aria-hidden="true"></span>
          <span class="w-avatar" aria-hidden="true">
            <?= e(mb_strtoupper(mb_substr($currentUser['full_name'] ?? '?', 0, 1))) ?>
          </span>
          <span>
            Welcome back, <strong><?= e($currentUser['full_name'] ?? 'Guest') ?></strong> — your table is ready.
          </span>
        </div>
      <?php else: ?>
        <div class="eyebrow reveal">A little pause in your everyday</div>
      <?php endif; ?>

      <h1 class="reveal">
        <?= e($titleLine1) ?><br>
        <em><?= e($titleLine2) ?></em>
      </h1>

      <p class="hero-copy reveal"><?= e($subtitle) ?></p>

      <div class="hero-actions reveal">
        <a href="#menu" class="btn-coffee">Explore the menu <i class="bi bi-arrow-up-right"></i></a>
        <a href="#story" class="btn-coffee btn-outline-coffee">Discover our story</a>
      </div>
    </div>

    <div class="hero-scroll">Scroll to discover</div>
    <div class="hero-note">Coffee · Conversation · Community</div>
  </header>

  <section class="intro section-pad" id="story">
    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6 reveal">
          <div class="intro-image-wrap">
            <img class="intro-image" src="<?= e($storyImgUrl) ?>" alt="Freshly brewed coffee being prepared by a barista" loading="lazy">
            <div class="round-stamp"><?= $stampHtml ?></div>
          </div>
        </div>
        <div class="col-lg-6 reveal">
          <div class="intro-copy ms-lg-5">
            <div class="eyebrow"><?= e($storyEyebrow) ?></div>
            <h2 class="section-title"><?= e($storyTitle1) ?><br><?= e($storyTitle2) ?></h2>
            <p class="section-copy"><?= e($storyPara1) ?></p>
            <?php if ($storyPara2 !== ''): ?>
              <p class="section-copy"><?= e($storyPara2) ?></p>
            <?php endif; ?>
            <a class="text-link" href="<?= e($storyBtnLink) ?>"><?= e($storyBtnText) ?> <i class="bi bi-arrow-up-right"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="marquee" aria-label="<?= e(implode(', ', $marqueeItems)) ?>">
    <div class="marquee-track">
      <div><?= $marqueeHtml ?></div>
      <div aria-hidden="true"><?= $marqueeHtml ?></div>
    </div>
  </div>

  <section class="menu-section section-pad" id="menu">
    <div class="container">
      <div class="text-center reveal">
        <div class="eyebrow">Made to be savoured</div>
        <h2 class="section-title mt-3">A menu worth<br>slowing down for.</h2>
        <p class="section-copy mx-auto mt-3" style="max-width:510px">From your everyday favourites to something new to fall in love with. Explore a few of our café classics.</p>
      </div>

      <?php if (!empty($visibleCategories)): ?>
        <div class="menu-tabs reveal" role="group" aria-label="Filter menu items">
          <button class="menu-tab active" data-filter="all">All favourites</button>
          <?php foreach ($visibleCategories as $cat): ?>
            <button class="menu-tab" data-filter="<?= e($cat['slug']) ?>"><?= e($cat['label']) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="row g-3 g-lg-4" id="menuGrid">
          <?php foreach ($visibleCategories as $cat): ?>
            <?php foreach ($itemsByCat[(int)$cat['id']] as $item): ?>
              <?php
                $price = isset($item['price']) && $item['price'] !== ''
                    ? '₹' . number_format((float)$item['price'], 0)
                    : '';
                $img = menu_img_url(
                    (string)$item['image_path'],
                    'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=350&q=80'
                );
              ?>
              <div class="col-lg-6 menu-item" data-category="<?= e($cat['slug']) ?>">
                <article class="menu-card">
                  <img src="<?= e($img) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                  <div>
                    <h3><?= e($item['name']) ?></h3>
                    <p><?= e($item['description']) ?></p>
                  </div>
                  <?php if ($price !== ''): ?>
                    <div class="menu-price"><?= e($price) ?></div>
                  <?php endif; ?>
                </article>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="menu-footnote">Our menu is being updated — check back soon.</p>
      <?php endif; ?>

      <p class="menu-footnote">A sample menu for website preview. Update dishes, ingredients and prices to match your café.</p>
      <div class="text-center mt-4 reveal"><a href="#visit" class="btn-coffee w-100 w-md-auto">Ask about today's specials <i class="bi bi-arrow-up-right"></i></a></div>
    </div>
  </section>

  <section class="feature">
    <div class="feature-bg"
         style="background-image:
                  linear-gradient(90deg, rgba(26,17,12,.88), rgba(26,17,12,.22)),
                  url('<?= e($featureBgUrl) ?>');"
         aria-hidden="true"></div>
    <div class="container feature-content">
      <div class="eyebrow reveal"><?= e($featureEyebrow) ?></div>
      <h2 class="reveal">
        <?= e($featureTitle1) ?><br>
        <em style="color:<?= e($featureTitle2Col) ?>"><?= e($featureTitle2) ?></em>
      </h2>
      <p class="reveal"><?= e($featureDesc) ?></p>
      <a href="<?= e($featureCtaLink) ?>" class="btn-coffee mt-3 reveal">
        <?= e($featureCtaText) ?> <i class="bi bi-arrow-up-right"></i>
      </a>
    </div>
  </section>

  <section class="gallery section-pad" id="gallery">
    <div class="container">
      <div class="row align-items-end g-3">
        <div class="col-lg-7 reveal"><div class="eyebrow">A little look inside</div><h2 class="section-title mt-3 mb-0">Come for the coffee.<br>Stay for the feeling.</h2></div>
        <div class="col-lg-5 reveal"><p class="section-copy mb-1">A cosy corner, your favourite people, the smell of freshly brewed coffee. There's always room for one more moment.</p></div>
      </div>
      <div class="gallery-grid reveal">
        <a href="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1400&q=90" class="gallery-link"><img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=900&q=85" alt="Warm and inviting café interior" loading="lazy"></a>
        <a href="https://images.unsplash.com/photo-1493857671505-72967e2e2760?auto=format&fit=crop&w=1400&q=90" class="gallery-link"><img src="https://images.unsplash.com/photo-1493857671505-72967e2e2760?auto=format&fit=crop&w=700&q=85" alt="Coffee and café table" loading="lazy"></a>
        <a href="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1400&q=90" class="gallery-link"><img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=700&q=85" alt="Café seating and atmosphere" loading="lazy"></a>
        <a href="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1600&q=90" class="gallery-link"><img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1100&q=85" alt="Barista preparing fresh coffee" loading="lazy"></a>
      </div>
    </div>
  </section>

  <section class="values section-pad">
    <div class="container">
      <div class="text-center reveal"><div class="eyebrow">The little things matter</div><h2 class="section-title mt-3">Made with intention.</h2></div>
      <div class="row g-3 g-lg-4 mt-4">
        <div class="col-md-4 reveal"><div class="value-card"><i class="bi bi-cup-hot"></i><h3>Thoughtful coffee</h3><p>Good beans, careful brewing and a little love in every cup.</p></div></div>
        <div class="col-md-4 reveal"><div class="value-card"><i class="bi bi-flower1"></i><h3>Fresh by nature</h3><p>Freshly prepared plates and ingredients chosen with care.</p></div></div>
        <div class="col-md-4 reveal"><div class="value-card"><i class="bi bi-heart"></i><h3>Warm hospitality</h3><p>A welcoming space where everyone has a seat at the table.</p></div></div>
      </div>
    </div>
  </section>

  <section class="quote-section section-pad">
    <div class="container reveal">
      <div class="quote-mark">“</div>
      <p class="quote-text">Sometimes all you need is a good cup of coffee and a place that feels like yours.</p>
      <div class="quote-author">Your table is waiting at Café Krunal</div>
    </div>
  </section>

  <section class="visit section-pad" id="visit">
    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-5 reveal">
          <div class="eyebrow"><?= e($visitEyebrow) ?></div>
          <h2 class="section-title mt-3"><?= e($visitTitle1) ?><br><?= e($visitTitle2) ?></h2>
          <p class="section-copy mt-4"><?= e($visitCopy) ?></p>
          <div class="visit-detail">
            <h3><?= e($visitFindHeading) ?></h3>
            <p><?= e($visitFindLine1) ?><?php if ($visitFindLine2 !== ''): ?><br><?= e($visitFindLine2) ?><?php endif; ?></p>
          </div>
          <div class="visit-detail">
            <h3><?= e($visitHoursHead) ?></h3>
            <p>
              <?= e($visitHoursDays) ?><br>
              <?= e($visitHoursTime) ?>
              <?php if ($visitHoursNote !== ''): ?>
                <small style="color:#bda58e"><?= e($visitHoursNote) ?></small>
              <?php endif; ?>
            </p>
          </div>
          <div class="visit-detail">
            <h3><?= e($visitContactHead) ?></h3>
            <p><?= e($visitPhone) ?><br><?= e($visitEmail) ?></p>
          </div>
        </div>
        <div class="col-lg-7 reveal">
          <form class="booking-form" id="bookingForm" novalidate>
            <h3><?= e($visitFormTitle) ?></h3>
            <div id="formAlert" class="d-none" role="alert"></div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="guestName">Your name</label>
                <input class="form-control" id="guestName" name="name" placeholder="Full name"
                       value="<?= $currentUser ? e($currentUser['full_name']) : '' ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="guestPhone">Phone number</label>
                <input class="form-control" id="guestPhone" name="phone" type="tel"
                       pattern="[0-9+\-\s]{8,15}" placeholder="+91" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="guestDate">Date</label>
                <input class="form-control" id="guestDate" name="date" type="date" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="guestTime">Time</label>
                <select class="form-select" id="guestTime" name="time" required>
                  <option value="">Choose a time</option>
                  <?php foreach ($timeOptions as $t): ?>
                    <option><?= e($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="guestCount">Number of guests</label>
                <select class="form-select" id="guestCount" name="guests" required>
                  <option value="">Select guests</option>
                  <?php foreach ($guestOptions as $g): ?>
                    <option><?= e($g) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="guestEmail">Email address</label>
                <input class="form-control" id="guestEmail" name="email" type="email"
                       placeholder="you@example.com"
                       value="<?= $currentUser ? e($currentUser['email']) : '' ?>">
              </div>
              <div class="col-12">
                <label class="form-label" for="guestMessage">A little note (optional)</label>
                <textarea class="form-control" id="guestMessage" name="message"
                          placeholder="Any special requests?"></textarea>
              </div>
              <div class="col-12">
                <button class="btn-coffee w-100" type="submit" id="bookingSubmit">
                  Request a reservation <i class="bi bi-arrow-up-right"></i>
                </button>
                <p class="small text-secondary mt-3 mb-0" id="formMessage" aria-live="polite">
                  <?= e($visitFormNote) ?>
                </p>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="container">
      <div class="row g-4 g-lg-5">
        <div class="col-lg-4">
          <a class="brand" href="#home"><span class="brand-mark">K</span> café krunal<span style="color:#b58a62">.</span></a>
          <p class="mt-4" style="max-width:310px">A neighbourhood café for thoughtful coffee, freshly made food and moments worth taking your time for.</p>
          <div class="socials mt-4"><a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a><a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a><a href="#" aria-label="Pinterest"><i class="bi bi-pinterest"></i></a></div>
        </div>
        <div class="col-6 col-lg-2"><h4>Explore</h4><p><a href="#story">Our Story</a><br><a href="#menu">Our Menu</a><br><a href="#gallery">The Space</a><br><a href="#visit">Find Us</a></p></div>
        <div class="col-6 col-lg-2"><h4>Visit</h4><p>Your café address<br>Surat, Gujarat<br>India</p></div>
        <div class="col-lg-4"><h4>Come on in</h4><p>Monday – Sunday<br>9:00 AM – 10:00 PM</p><a class="text-link mt-0" href="#visit" style="color:#e3c19e">Plan your visit <i class="bi bi-arrow-up-right"></i></a></div>
      </div>
      <div class="footer-bottom"><small>© <span id="year"></span> Café Krunal. All rights reserved.</small><small>Made for good coffee & good company.</small></div>
    </div>
  </footer>

  <button class="to-top" id="toTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(function () {
      /* ---------- Nav + scroll ---------- */
      const $nav   = $('#mainNav');
      const $toTop = $('#toTop');

      function updateScrollUI() {
        const y = window.scrollY;
        $nav.toggleClass('scrolled', y > 30);
        $toTop.toggleClass('show', y > 500);
      }
      updateScrollUI();
      $(window).on('scroll', updateScrollUI);
      $toTop.on('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

      $('.navbar .nav-link, .navbar .btn-coffee').on('click', function () {
        const menu = document.getElementById('navMenu');
        if (menu.classList.contains('show')) bootstrap.Collapse.getOrCreateInstance(menu).hide();
      });

      /* ---------- Reveal on scroll ---------- */
      const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12 });
      document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

      /* ---------- Menu filter ---------- */
      $('.menu-tab').on('click', function () {
        const filter = $(this).data('filter');
        $('.menu-tab').removeClass('active');
        $(this).addClass('active');
        $('.menu-item').each(function () {
          const show = filter === 'all' || String($(this).data('category')) === String(filter);
          $(this).toggleClass('hide', !show);
        });
      });

      /* ---------- Booking form ---------- */
      const $bookingForm  = $('#bookingForm');
      const $bookingBtn   = $('#bookingSubmit');
      const $bookingAlert = $('#formAlert');
      const $formMessage  = $('#formMessage');
      const dateInput     = document.getElementById('guestDate');

      if (dateInput) {
        const today = new Date();
        dateInput.min = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
          .toISOString().split('T')[0];
      }

      function showBookingAlert(type, text) {
        $bookingAlert
          .removeClass('d-none alert-success alert-danger')
          .addClass(type === 'ok' ? 'alert alert-success' : 'alert alert-danger')
          .text(text);
      }

      $bookingForm.on('submit', function (e) {
        e.preventDefault();
        if (!this.reportValidity()) return;

        const fd = new FormData(this);
        const originalHtml = $bookingBtn.html();

        $bookingBtn.prop('disabled', true).html('Sending… <i class="bi bi-arrow-repeat"></i>');
        $bookingAlert.addClass('d-none');
        $formMessage.text('');

        fetch('reservations_handler.php', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async (res) => {
          let data;
          try { data = await res.json(); }
          catch { throw new Error('Unexpected server response.'); }
          return { ok: res.ok, data };
        })
        .then(({ ok, data }) => {
          if (ok && data.ok) {
            showBookingAlert('ok', data.message || 'Reservation received. Thank you!');
            $bookingForm[0].reset();
            <?php if ($currentUser): ?>
            $('#guestName').val('<?= e($currentUser['full_name']) ?>');
            $('#guestEmail').val('<?= e($currentUser['email']) ?>');
            <?php endif; ?>
            if (dateInput) {
              const t = new Date();
              dateInput.min = new Date(t.getTime() - t.getTimezoneOffset() * 60000)
                .toISOString().split('T')[0];
            }
          } else {
            showBookingAlert('error', data.error || 'Could not save your reservation.');
          }
        })
        .catch((err) => {
          console.error(err);
          showBookingAlert('error', 'Network error. Please try again.');
        })
        .finally(() => {
          $bookingBtn.prop('disabled', false).html(originalHtml);
        });
      });

      /* ---------- Gallery lightbox ---------- */
      $('.gallery-link').on('click', function (e) {
        e.preventDefault();
        window.open($(this).attr('href'), '_blank', 'noopener');
      });

      /* ---------- Year ---------- */
      $('#year').text(new Date().getFullYear());
    });
  </script>
</body>
</html>