<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/hotel_save.php';
$me = require_admin();

$isEdit = false; $image = null;
$v = ['name' => '', 'email' => '', 'address' => '', 'phone' => '', 'city' => '', 'amenities' => '', 'rating' => '3', 'description' => ''];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = hotel_collect();
    $newImage = null;
    if (!$errors) {
        try { $newImage = handle_image_upload('image'); } catch (RuntimeException $ex) { $errors['image'] = $ex->getMessage(); }
    }
    if (!$errors) {
        db_exec('INSERT INTO hotel (Hotel_city, Hotel_amenities, Hotel_name, Hotel_description, Hotel_address, Hotel_phone_no, Hotel_email, Hotel_rating, Hotel_image) VALUES (?,?,?,?,?,?,?,?,?)',
            'sssssssis', [$v['city'], $v['amenities'], $v['name'], $v['description'], $v['address'], $v['phone'], $v['email'], (int) $v['rating'], $newImage]);
        flash('success', 'Hotel added.');
        redirect('hotel_select.php');
    }
}
$page_title = 'Add hotel';
include 'header.php';
page_open('Hotels');
include __DIR__ . '/includes/hotel_form.php';
page_close();
include 'footer.php';
