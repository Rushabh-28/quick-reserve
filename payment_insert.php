<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/payment_save.php';
$me = require_admin();

$bookings = payment_options();
$isEdit = false;
$v = ['booking' => (string) ($_GET['booking'] ?? ''), 'amount' => '', 'method' => 'cash', 'status' => 'paid'];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = payment_collect($bookings);
    if (!$errors) {
        db_exec('INSERT INTO payment (Booking_id, Amount, Payment_method, Status) VALUES (?,?,?,?)', 'idss', [(int) $v['booking'], (float) $v['amount'], $v['method'], $v['status']]);
        sync_payment_status((int) $v['booking']);
        flash('success', 'Payment recorded.');
        redirect('payment_select.php');
    }
}
$page_title = 'Add payment';
include 'header.php';
page_open('Payments');
include __DIR__ . '/includes/payment_form.php';
page_close();
include 'footer.php';
