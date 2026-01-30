<?php
// Google API Configuration
require_once __DIR__ . '/../includes/Env.php';
Env::load(__DIR__ . '/../.env');

define('GOOGLE_CLIENT_ID', Env::get('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', Env::get('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URL', Env::get('GOOGLE_REDIRECT_URL', 'http://localhost/Project/EntryX/api/auth.php?action=google_callback'));
?>