<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_lib.php';
require_once __DIR__ . '/includes/coupon_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM coupon WHERE Coupon_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Coupon not found.'); redirect('coupon_select.php'); }

$isEdit = true;
$v = ['code' => $row['Code'], 'description' => $row['Description'], 'type' => $row['Type'], 'value' => $row['Value'], 'min_amount' => $row['Min_amount'],
      'max_discount' => (string) $row['Max_discount'], 'valid_from' => (string) $row['Valid_from'], 'valid_to' => (string) $row['Valid_to'],
      'usage_limit' => (string) $row['Usage_limit'], 'active' => (string) (int) $row['Active']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = coupon_collect($id);
    if (!$errors) {
        db_exec('UPDATE coupon SET Code=?, Description=?, Type=?, Value=?, Min_amount=?, Max_discount=?, Valid_from=?, Valid_to=?, Usage_limit=?, Active=? WHERE Coupon_id=?',
            'sssddd' . 'ssiii', array_merge(coupon_params($v), [$id]));
        flash('success', 'Coupon updated.');
        redirect('coupon_select.php');
    }
}
$page_title = 'Edit coupon';
include 'header.php';
page_open('Coupons');
include __DIR__ . '/includes/coupon_form.php';
page_close();
include 'footer.php';
