<?php
// ============================================================
//  logout.php  —  destroys the session and redirects home
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// Also clear the "remember me" cookie
if (isset($_COOKIE['cafe_remember'])) {
    setcookie('cafe_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Destroy the session
session_destroy();

// Redirect home
header('Location: index.php');
exit;