<?php
/** Receives the payment result (Razorpay Checkout callback or the demo gateway), verifies it, and confirms the booking. */
require_once __DIR__ . '/includes/client.php';
$g = require_guest();
if (!is_post()) { redirect('my_bookings.php'); }
csrf_verify();

$orderId = post_str('razorpay_order_id');
$ref     = post_str('ref');
$pay = db_one('SELECT p.*, b.Booking_ref, b.User_id FROM payment p JOIN booking b ON b.Booking_id = p.Booking_id WHERE p.Gateway_order_id = ?', 's', [$orderId]);
if (!$pay || (int) $pay['User_id'] !== (int) $g['User_id']) {
    flash('error', 'We could not match that payment to your account.');
    redirect('my_bookings.php');
}
$ref = $pay['Booking_ref'];
if ($pay['Status'] === 'paid') { redirect('booking_success.php?ref=' . urlencode($ref)); }

$failMsg = '';
$paymentId = post_str('razorpay_payment_id');
$signature = post_str('razorpay_signature');

if (!razorpay_live() && ($_POST['demo_action'] ?? '') !== '') {
    // Demo gateway: build a payment id + signature exactly like Razorpay would, then run the same verification.
    if ($_POST['demo_action'] === 'fail') { $failMsg = 'The demo payment was declined.'; }
    else { $paymentId = 'pay_DEMO' . strtoupper(bin2hex(random_bytes(6))); $signature = razorpay_sign($orderId, $paymentId); }
} elseif (post_str('failed') !== '') {
    $failMsg = mb_substr(post_str('failed'), 0, 200);
}

if ($failMsg === '' && !razorpay_verify_signature($orderId, $paymentId, $signature)) {
    error_log('Quick Reserve: invalid payment signature for order ' . $orderId);
    $failMsg = 'We could not verify this payment. If money was deducted it will be refunded automatically.';
}

if ($failMsg !== '') {
    db_exec("UPDATE payment SET Status = 'failed' WHERE Payment_id = ? AND Status = 'created'", 'i', [(int) $pay['Payment_id']]);
    flash('error', $failMsg . ' You can try again.');
    redirect('pay.php?ref=' . urlencode($ref));
}

razorpay_record_success($orderId, $paymentId, $signature);
redirect('booking_success.php?ref=' . urlencode($ref) . '&paid=1');
