<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/payment_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM payment WHERE Payment_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Payment not found.'); redirect('payment_select.php'); }

$bookings = payment_options();
$isEdit = true;
$v = ['booking' => (string) $row['Booking_id'], 'amount' => (string) $row['Amount'], 'method' => $row['Payment_method'], 'status' => $row['Status']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = payment_collect($bookings);
    if (!$errors) {
        db_exec('UPDATE payment SET Booking_id=?, Amount=?, Payment_method=?, Status=? WHERE Payment_id=?', 'idssi', [(int) $v['booking'], (float) $v['amount'], $v['method'], $v['status'], $id]);
        sync_payment_status((int) $v['booking']);
        if ((int) $row['Booking_id'] !== (int) $v['booking']) { sync_payment_status((int) $row['Booking_id']); }
        flash('success', 'Payment updated.');
        redirect('payment_select.php');
    }
}
$page_title = 'Edit payment';
include 'header.php';
page_open('Payments');
include __DIR__ . '/includes/payment_form.php';
page_close();
include 'footer.php';
