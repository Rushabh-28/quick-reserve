<?php
/**
 * Database connection for Quick Reserve.
 *
 * Settings are read, in this order, from:
 *   1. config/config.local.php  (copy of config.sample.php, NOT committed to git)
 *   2. environment variables    (QR_DB_HOST, QR_DB_USER, QR_DB_PASS, QR_DB_NAME)
 *   3. XAMPP/WAMP defaults      (localhost / root / no password / hotel_managment)
 */
$qr_config = [
    'db_host' => getenv('QR_DB_HOST') ?: 'localhost',
    'db_user' => getenv('QR_DB_USER') ?: 'root',
    'db_pass' => getenv('QR_DB_PASS') !== false ? getenv('QR_DB_PASS') : '',
    'db_name' => getenv('QR_DB_NAME') ?: 'hotel_managment',

    // Outgoing e-mail (forgot-password OTP). Leave blank to disable e-mail.
    'smtp_host'     => getenv('QR_SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port'     => (int) (getenv('QR_SMTP_PORT') ?: 587),
    'smtp_user'     => getenv('QR_SMTP_USER') ?: '',
    'smtp_pass'     => getenv('QR_SMTP_PASS') ?: '',
    'smtp_from'     => getenv('QR_SMTP_FROM') ?: '',
    'smtp_from_name'=> 'Quick Reserve',

    // Razorpay (https://dashboard.razorpay.com > Settings > API Keys). Leave key id/secret empty
    // to run the built-in DEMO payment gateway (no real money, great for testing).
    'razorpay_key_id'         => getenv('QR_RAZORPAY_KEY_ID') ?: '',
    'razorpay_key_secret'     => getenv('QR_RAZORPAY_KEY_SECRET') ?: '',
    'razorpay_webhook_secret' => getenv('QR_RAZORPAY_WEBHOOK_SECRET') ?: '',

    // Business details printed on invoices.
    'business_name'    => 'Quick Reserve Hospitality Pvt. Ltd.',
    'business_address' => '12 MG Road, Bengaluru, Karnataka 560001',
    'business_gstin'   => '29ABCDE1234F1Z5',   // sample GSTIN - replace with yours
    'business_email'   => 'support@quickreserve.example',
    'hold_minutes'     => 15,                  // unpaid bookings hold the room this long
];

$qr_local = __DIR__ . '/config.local.php';
if (is_file($qr_local)) {
    $qr_config = array_merge($qr_config, require $qr_local);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($qr_config['db_host'], $qr_config['db_user'], $qr_config['db_pass'], $qr_config['db_name']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Quick Reserve DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check config/config.local.php and that the database is imported.');
}
