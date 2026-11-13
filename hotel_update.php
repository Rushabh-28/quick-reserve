<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/hotel_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM hotel WHERE Hotel_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Hotel not found.'); redirect('hotel_select.php'); }

$isEdit = true; $image = $row['Hotel_image'];
$v = ['name' => $row['Hotel_name'], 'email' => $row['Hotel_email'], 'address' => $row['Hotel_address'],
      'phone' => $row['Hotel_phone_no'], 'city' => $row['Hotel_city'], 'amenities' => $row['Hotel_amenities'], 'rating' => (string) $row['Hotel_rating'], 'description' => $row['Hotel_description']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = hotel_collect();
    $newImage = null;
    if (!$errors) {
        try { $newImage = handle_image_upload('image'); } catch (RuntimeException $ex) { $errors['image'] = $ex->getMessage(); }
    }
    if (!$errors) {
        $final = $newImage ?? $row['Hotel_image'];
        db_exec('UPDATE hotel SET Hotel_city=?, Hotel_amenities=?, Hotel_name=?, Hotel_description=?, Hotel_address=?, Hotel_phone_no=?, Hotel_email=?, Hotel_rating=?, Hotel_image=? WHERE Hotel_id=?',
            'sssssssisi', [$v['city'], $v['amenities'], $v['name'], $v['description'], $v['address'], $v['phone'], $v['email'], (int) $v['rating'], $final, $id]);
        if ($newImage) { delete_upload($row['Hotel_image']); }
        flash('success', 'Hotel updated.');
        redirect('hotel_select.php');
    }
}
$page_title = 'Edit hotel';
include 'header.php';
page_open('Hotels');
include __DIR__ . '/includes/hotel_form.php';
page_close();
include 'footer.php';
