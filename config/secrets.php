<?php
// MASTER ADMIN CREDENTIALS
require_once __DIR__ . '/../includes/Env.php';
Env::load(__DIR__ . '/../.env');

define('MASTER_ADMIN_EMAIL', Env::get('MASTER_ADMIN_EMAIL', 'admin@entryx.com'));
define('MASTER_ADMIN_PASS', Env::get('MASTER_ADMIN_PASS', 'admin123'));
define('MASTER_ADMIN_ID', (int) Env::get('MASTER_ADMIN_ID', 999999));
?>