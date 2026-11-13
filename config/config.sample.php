<?php
/**
 * Copy this file to config/config.local.php and fill in your values.
 * config.local.php is listed in .gitignore, so your passwords never reach GitHub.
 */
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'hotel_managment',

    // For the "Forgot password" e-mail. With Gmail, create an *App Password*
    // (Google Account > Security > 2-Step Verification > App passwords).
    'smtp_host'      => 'smtp.gmail.com',
    'smtp_port'      => 587,
    'smtp_user'      => 'you@gmail.com',
    'smtp_pass'      => 'your-app-password',
    'smtp_from'      => 'you@gmail.com',
    'smtp_from_name' => 'Quick Reserve',

    // Razorpay keys. Empty = DEMO gateway (no real payments). Use rzp_test_... keys to test for real.
    'razorpay_key_id'         => '',
    'razorpay_key_secret'     => '',
    'razorpay_webhook_secret' => '',

    // Printed on invoices
    'business_name'    => 'Your Hotel Company Pvt. Ltd.',
    'business_address' => 'Street, City, State PIN',
    'business_gstin'   => '',
    'business_email'   => 'support@example.com',
    'hold_minutes'     => 15,
];
