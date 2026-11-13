<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('booking_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db_exec('DELETE FROM `booking` WHERE Booking_id = ?', 'i', [$id]);
    
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'Could not delete this booking.');
}
redirect('booking_select.php');
