<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('payment_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

$old = db_one('SELECT Booking_id FROM payment WHERE Payment_id = ?', 'i', [$id]);
try {
    db_exec('DELETE FROM `payment` WHERE Payment_id = ?', 'i', [$id]);
    
    if ($old) { require_once __DIR__ . '/includes/booking_lib.php'; sync_payment_status((int) $old['Booking_id']); }
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'Could not delete this payment.');
}
redirect('payment_select.php');
