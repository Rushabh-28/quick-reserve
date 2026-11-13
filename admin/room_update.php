<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/room_save.php';
$me = require_admin();

$id  = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM room WHERE Room_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'Room not found.'); redirect('room_select.php'); }

[$hotels, $types] = room_options();
$isEdit = true; $image = $row['Room_image'];
$v = ['hotel' => (string) $row['Hotel_id'], 'type' => (string) $row['Room_type_id'], 'no' => $row['Room_no'], 'availability' => $row['Room_availability']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors] = room_collect($hotels, $types, $id);
    $newImage = null;
    if (!$errors) {
        try { $newImage = handle_image_upload('room_image'); } catch (RuntimeException $ex) { $errors['room_image'] = $ex->getMessage(); }
    }
    if (!$errors) {
        db_exec('UPDATE room SET Hotel_id=?, Room_type_id=?, Room_no=?, Room_availability=?, Room_image=? WHERE Room_id=?',
            'iisssi', [(int) $v['hotel'], (int) $v['type'], $v['no'], $v['availability'], $newImage ?? $row['Room_image'], $id]);
        if ($newImage) { delete_upload($row['Room_image']); }
        flash('success', 'Room updated.');
        redirect('room_select.php');
    }
}
$page_title = 'Edit room';
include 'header.php';
page_open('Rooms');
include __DIR__ . '/includes/room_form.php';
page_close();
include 'footer.php';
