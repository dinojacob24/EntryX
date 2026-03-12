<?php
// Razorpay Payment Gateway Configuration
require_once __DIR__ . '/../includes/Env.php';
Env::load(__DIR__ . '/../.env');

define('RAZORPAY_KEY_ID', Env::get('RAZORPAY_KEY_ID', ''));
define('RAZORPAY_KEY_SECRET', Env::get('RAZORPAY_KEY_SECRET', ''));

// Path to the Razorpay PHP SDK (non-composer installation)
define('RAZORPAY_SDK_PATH', __DIR__ . '/../razorpay-php-2.9.2/Razorpay.php');
?>