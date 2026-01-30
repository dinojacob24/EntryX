<?php
require_once __DIR__ . '/../includes/Env.php';
Env::load(__DIR__ . '/../.env');

$host = Env::get('DB_HOST', 'localhost');
$db_name = Env::get('DB_NAME', 'entryx');
$username = Env::get('DB_USER', 'root');
$password = Env::get('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}