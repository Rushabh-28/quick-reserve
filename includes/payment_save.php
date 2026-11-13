<?php
require_once __DIR__ . '/booking_lib.php';
function payment_options(): array
{
    return array_column(db_all(
        'SELECT b.Booking_id, CONCAT("#", b.Booking_id, " - ", u.User_name, " - total ", FORMAT(b.Total_price, 2)) AS label
           FROM booking b JOIN `user` u ON u.User_id = b.User_id ORDER BY b.Booking_id DESC'), 'label', 'Booking_id');
}

function payment_collect(array $bookings): array
{
    $v = ['booking' => post_str('booking_id'), 'amount' => post_str('amount'), 'method' => post_str('method'), 'status' => post_str('status')];
    $e = [];
    if (!isset($bookings[(int) $v['booking']])) { $e['booking_id'] = 'Select a booking'; }
    if (!is_numeric($v['amount']) || (float) $v['amount'] <= 0 || (float) $v['amount'] > 99999999) { $e['amount'] = 'Enter an amount greater than 0'; }
    if (!in_array($v['method'], ['cash', 'card', 'upi', 'netbanking', 'razorpay'], true)) { $e['method'] = 'Choose a payment method'; }
    if (!in_array($v['status'], ['paid', 'refunded', 'failed'], true)) { $e['status'] = 'Choose a status'; }
    return [$v, $e];
}
