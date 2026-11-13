<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_save.php';
$me = require_admin();

[$guests, $rooms] = booking_options();
$isEdit = false;
$v = ['user' => '', 'room' => '', 'in' => '', 'out' => '', 'guests' => '1', 'coupon' => '', 'status' => 'confirmed'];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = booking_collect($guests, $rooms);
    if (!$errors) {
        try {
            $res = create_booking(['user_id' => (int) $v['user'], 'room_id' => (int) $v['room'], 'in' => $v['in'], 'out' => $v['out'],
                'guests' => (int) $v['guests'], 'coupon_code' => $v['coupon'], 'status' => $v['status'], 'allow_past' => true]);
            flash('success', 'Booking ' . $res['ref'] . ' added. Total: ' . money($res['quote']['total']));
            redirect('booking_select.php');
        } catch (RuntimeException $ex) {
            $errors[str_contains($ex->getMessage(), 'oupon') ? 'coupon' : 'room_id'] = $ex->getMessage();
        }
    }
}
$page_title = 'Add booking';
include 'header.php';
page_open('Bookings');
include __DIR__ . '/includes/booking_form.php';
page_close();
include 'footer.php';
