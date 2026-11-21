<?php

declare(strict_types=1);

session_start();

$DB_HOST = 'localhost:3307';
$DB_NAME = 'cafe_krunal';
$DB_USER = 'root';
$DB_PASS = '';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

?>