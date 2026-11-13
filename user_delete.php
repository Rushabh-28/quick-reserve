<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('user_select.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
if ($id === (int) $me["User_id"]) { flash("error", "You cannot delete your own account."); redirect("user_select.php"); }
$target = db_one("SELECT is_admin FROM `user` WHERE User_id = ?", "i", [$id]);
if ($target && (int) $target["is_admin"] === 1 && (int) db_one("SELECT COUNT(*) c FROM `user` WHERE is_admin = 1")["c"] <= 1) {
    flash("error", "At least one admin must remain."); redirect("user_select.php");
}
try {
    db_exec('DELETE FROM `user` WHERE User_id = ?', 'i', [$id]);
    
    flash('success', 'Deleted.');
} catch (mysqli_sql_exception $e) {
    flash('error', 'Could not delete this user.');
}
redirect('user_select.php');
