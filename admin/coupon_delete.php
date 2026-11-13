<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('coupon_select.php'); }
csrf_verify();
db_exec('DELETE FROM coupon WHERE Coupon_id = ?', 'i', [(int) ($_POST['id'] ?? 0)]);
flash('success', 'Coupon deleted. Existing bookings keep their discount.');
redirect('coupon_select.php');
