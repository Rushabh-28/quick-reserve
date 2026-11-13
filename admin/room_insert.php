<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/room_save.php';
$me = require_admin();

[$hotels, $types] = room_options();
$isEdit = false; $image = null;
$v = ['hotel' => '', 'type' => '', 'no' => '', 'availability' => 'yes'];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = room_collect($hotels, $types);
    $newImage = null;
    if (!$errors) {
        try { $newImage = handle_image_upload('room_image'); } catch (RuntimeException $ex) { $errors['room_image'] = $ex->getMessage(); }
    }
    if (!$errors) {
        db_exec('INSERT INTO room (Hotel_id, Room_type_id, Room_no, Room_availability, Room_image) VALUES (?,?,?,?,?)',
            'iisss', [(int) $v['hotel'], (int) $v['type'], $v['no'], $v['availability'], $newImage]);
        flash('success', 'Room added.');
        redirect('room_select.php');
    }
}
$page_title = 'Add room';
include 'header.php';
page_open('Rooms');
if (!$hotels || !$types) {
    echo '<div class="alert alert-warning">Add at least one hotel and one room type before adding rooms.</div>';
}
include __DIR__ . '/includes/room_form.php';
page_close();
include 'footer.php';
