<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('hotel_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$old = db_one("SELECT Hotel_image FROM hotel WHERE Hotel_id = ?", "i", [$id]);
$roomImgs = array_column(db_all("SELECT Room_image FROM room WHERE Hotel_id = ?", "i", [$id]), "Room_image");
try {
    db_exec('DELETE FROM `hotel` WHERE Hotel_id = ?', 'i', [$id]);
    delete_upload($old["Hotel_image"] ?? null); foreach ($roomImgs as $img) { delete_upload($img); }
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'Could not delete this hotel.');
}
redirect('hotel_select.php');
