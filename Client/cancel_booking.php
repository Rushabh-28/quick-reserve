<?php
require_once __DIR__ . '/includes/client.php';
$g = require_guest();
if (!is_post()) { redirect('my_bookings.php'); }
csrf_verify();
$b = db_one('SELECT * FROM booking WHERE Booking_ref = ? AND User_id = ?', 'si', [post_str('ref'), $g['User_id']]);
if (!$b)                      { flash('error', 'Booking not found.'); }
elseif (!booking_can_cancel($b)) { flash('error', 'This booking can no longer be cancelled online. Please contact the hotel.'); }
else {
    cancel_booking((int) $b['Booking_id']);
    flash('success', $b['Payment_status'] === 'paid' ? 'Booking cancelled. Your refund will be processed in 5–7 working days.' : 'Booking cancelled.');
}
redirect('my_bookings.php?tab=cancelled');
