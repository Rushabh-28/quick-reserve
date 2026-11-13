<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('room_type_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

try {
    db_exec('DELETE FROM `room_type` WHERE Room_type_id = ?', 'i', [$id]);
    
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'This room type is still used by rooms. Change or delete those rooms first.');
}
redirect('room_type_select.php');
