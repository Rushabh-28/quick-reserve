<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/room_type_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM room_type WHERE Room_type_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Room type not found.'); redirect('room_type_select.php'); }

$isEdit = true;
$v = ['name' => $row['Room_type_name'], 'description' => $row['Room_type_description'],
      'capacity' => (string) $row['Room_type_capacity'], 'price' => (string) $row['Room_type_price_per_night']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = room_type_collect();
    if (!$errors) {
        db_exec('UPDATE room_type SET Room_type_name=?, Room_type_description=?, Room_type_capacity=?, Room_type_price_per_night=? WHERE Room_type_id=?',
            'ssidi', [$v['name'], $v['description'], (int) $v['capacity'], (float) $v['price'], $id]);
        flash('success', 'Room type updated.');
        redirect('room_type_select.php');
    }
}
$page_title = 'Edit room type';
include 'header.php';
page_open('Room types');
include __DIR__ . '/includes/room_type_form.php';
page_close();
include 'footer.php';
