<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('room_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$old = db_one("SELECT Room_image FROM room WHERE Room_id = ?", "i", [$id]);
try {
    db_exec('DELETE FROM `room` WHERE Room_id = ?', 'i', [$id]);
    delete_upload($old["Room_image"] ?? null);
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'Could not delete this room.');
}
redirect('room_select.php');
