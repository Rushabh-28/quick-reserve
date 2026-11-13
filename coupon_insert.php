<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_lib.php';
require_once __DIR__ . '/includes/coupon_save.php';
$me = require_admin();

$isEdit = false;
$v = ['code' => '', 'description' => '', 'type' => 'percent', 'value' => '', 'min_amount' => '0', 'max_discount' => '', 'valid_from' => '', 'valid_to' => '', 'usage_limit' => '', 'active' => '1'];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = coupon_collect();
    if (!$errors) {
        db_exec('INSERT INTO coupon (Code, Description, Type, Value, Min_amount, Max_discount, Valid_from, Valid_to, Usage_limit, Active) VALUES (?,?,?,?,?,?,?,?,?,?)',
            'sssddd' . 'ssii', coupon_params($v));
        flash('success', 'Coupon added.');
        redirect('coupon_select.php');
    }
}
$page_title = 'Add coupon';
include 'header.php';
page_open('Coupons');
include __DIR__ . '/includes/coupon_form.php';
page_close();
include 'footer.php';
