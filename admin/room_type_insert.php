<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/room_type_save.php';
$me = require_admin();

$isEdit = false;
$v = ['name' => '', 'description' => '', 'capacity' => '2', 'price' => ''];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = room_type_collect();
    if (!$errors) {
        db_exec('INSERT INTO room_type (Room_type_name, Room_type_description, Room_type_capacity, Room_type_price_per_night) VALUES (?,?,?,?)',
            'ssid', [$v['name'], $v['description'], (int) $v['capacity'], (float) $v['price']]);
        flash('success', 'Room type added.');
        redirect('room_type_select.php');
    }
}
$page_title = 'Add room type';
include 'header.php';
page_open('Room types');
include __DIR__ . '/includes/room_type_form.php';
page_close();
include 'footer.php';
