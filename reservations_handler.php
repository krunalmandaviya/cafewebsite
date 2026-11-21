<?php
// ============================================================
//  reservations_handler.php  —  AJAX endpoint for booking form
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

/* ---------- Only accept POST ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

/* ---------- Helpers ---------- */
function field(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}
function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

/* ---------- Collect input ---------- */
$name    = field('name');
$phone   = field('phone');
$email   = field('email');
$date    = field('date');
$time    = field('time');
$guests  = field('guests');
$message = field('message');

/* ---------- Validate ---------- */
if ($name === '' || mb_strlen($name) > 120) {
    fail('Please enter your name (max 120 chars).');
}
if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
    fail('Please enter a valid phone number.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fail('Please choose a valid date.');
}
$today   = new DateTimeImmutable('today');
$chosen  = DateTimeImmutable::createFromFormat('Y-m-d', $date);
if (!$chosen || $chosen < $today) {
    fail('Please choose a date from today onwards.');
}
if ($time === '') {
    fail('Please choose a time.');
}
if ($guests === '' || !ctype_digit($guests) || (int)$guests < 1 || (int)$guests > 20) {
    fail('Please choose the number of guests (1–20).');
}
if (mb_strlen($message) > 1000) {
    fail('Message is too long (max 1000 chars).');
}

/* ---------- Persist ---------- */
try {
    $stmt = $pdo->prepare("
        INSERT INTO reservations
            (name, phone, email, res_date, res_time, guests, message, user_id, ip_address, status)
        VALUES
            (:name, :phone, :email, :res_date, :res_time, :guests, :message, :user_id, :ip, 'pending')
    ");

    $stmt->execute([
        ':name'     => $name,
        ':phone'    => $phone,
        ':email'    => $email !== '' ? $email : null,
        ':res_date' => $date,
        ':res_time' => $time,
        ':guests'   => (int)$guests,
        ':message'  => $message !== '' ? $message : null,
        ':user_id'  => !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        ':ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    echo json_encode([
        'ok'         => true,
        'id'         => (int)$pdo->lastInsertId(),
        'message'    => 'Thank you! Your reservation request has been received. We\'ll be in touch shortly.',
    ]);
} catch (Throwable $e) {
    error_log('Reservation insert failed: ' . $e->getMessage());
    fail('Something went wrong. Please try again or call us.', 500);
}