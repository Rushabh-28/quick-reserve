<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM booking WHERE Booking_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Booking not found.'); redirect('booking_select.php'); }

[$guests, $rooms] = booking_options();
$isEdit = true;
$v = ['user' => (string) $row['User_id'], 'room' => (string) $row['Room_id'], 'in' => $row['Check_in_date'],
      'out' => $row['Check_out_date'], 'guests' => (string) $row['Guests'], 'coupon' => (string) $row['Coupon_code'], 'status' => $row['Booking_status']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = booking_collect($guests, $rooms, $id);
    $v['coupon'] = (string) $row['Coupon_code'];
    if (!$errors) {
        $ppn    = room_price_per_night((int) $v['room']) ?? 0.0;
        $coupon = $row['Coupon_code'] ? db_one('SELECT * FROM coupon WHERE Code = ?', 's', [$row['Coupon_code']]) : null;
        $q = price_quote($ppn, nights_between($v['in'], $v['out']), $coupon);
        $pay = $row['Payment_status'];
        if ($v['status'] === 'cancelled' && $row['Booking_status'] !== 'cancelled' && $pay === 'paid') { $pay = 'refund_pending'; }
        db_exec('UPDATE booking SET User_id=?, Room_id=?, Guests=?, Check_in_date=?, Check_out_date=?, Subtotal=?, Discount=?, Tax=?, Total_price=?, Booking_status=?, Payment_status=?, Hold_expires=NULL WHERE Booking_id=?',
            'iiissddddssi', [(int) $v['user'], (int) $v['room'], (int) $v['guests'], $v['in'], $v['out'], $q['subtotal'], $q['discount'], $q['tax'], $q['total'], $v['status'], $pay, $id]);
        sync_payment_status($id);
        flash('success', 'Booking updated. New total: ' . money($q['total']));
        redirect('booking_select.php');
    }
}
$page_title = 'Edit booking';
include 'header.php';
page_open('Bookings');
include __DIR__ . '/includes/booking_form.php';
page_close();
include 'footer.php';
