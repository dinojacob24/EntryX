<?php
/**
 * SMTP Configuration for PHPMailer
 * To use real emails:
 * 1. Download PHPMailer from GitHub or Composer
 * 2. Place it in 'vendor/phpmailer'
 * 3. Set 'MAIL_ENABLED' to true
 */

require_once __DIR__ . '/../includes/Env.php';
Env::load(__DIR__ . '/../.env');

define('MAIL_ENABLED', Env::get('MAIL_ENABLED', 'false') === 'true');
define('SMTP_HOST', Env::get('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) Env::get('SMTP_PORT', 587));
define('SMTP_USER', Env::get('SMTP_USER', 'your-email@gmail.com'));
define('SMTP_PASS', Env::get('SMTP_PASS', 'your-app-password'));
define('SMTP_FROM', Env::get('SMTP_FROM', 'noreply@entryx.com'));
define('SMTP_FROM_NAME', Env::get('SMTP_FROM_NAME', 'EntryX Support'));
