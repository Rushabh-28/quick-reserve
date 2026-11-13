<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_admin();
if (!is_post()) { redirect('review_select.php'); }
csrf_verify();
db_exec('DELETE FROM review WHERE Review_id = ?', 'i', [(int) ($_POST['id'] ?? 0)]);
flash('success', 'Review removed.');
redirect('review_select.php');
