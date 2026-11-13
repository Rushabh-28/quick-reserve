<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/user_save.php';
$me = require_admin();

$id = (int) ($_GET['id'] ?? 0);
$row = db_one('SELECT * FROM `user` WHERE User_id = ?', 'i', [$id]);
if (!$row) { flash('error', 'User not found.'); redirect('user_select.php'); }

$isEdit = true;
$v = ['name' => $row['User_name'], 'email' => $row['User_email'], 'address' => $row['User_address'],
      'phone' => $row['User_phoneno'], 'is_admin' => (string) (int) $row['is_admin']];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors, $pw] = user_collect(true, $id);
    if (!$errors && $id === (int) $me['User_id'] && $v['is_admin'] !== '1') {
        $errors['is_admin'] = 'You cannot remove your own admin access.';
    }
    if (!$errors && (int) $row['is_admin'] === 1 && $v['is_admin'] !== '1'
        && (int) db_one('SELECT COUNT(*) c FROM `user` WHERE is_admin = 1')['c'] <= 1) {
        $errors['is_admin'] = 'At least one admin must remain.';
    }
    if (!$errors) {
        if ($pw !== '') {
            db_exec('UPDATE `user` SET User_name=?, User_email=?, User_address=?, User_phoneno=?, is_admin=?, User_password=? WHERE User_id=?',
                'ssssisi', [$v['name'], $v['email'], $v['address'], $v['phone'], (int) $v['is_admin'], password_hash($pw, PASSWORD_DEFAULT), $id]);
        } else {
            db_exec('UPDATE `user` SET User_name=?, User_email=?, User_address=?, User_phoneno=?, is_admin=? WHERE User_id=?',
                'ssssii', [$v['name'], $v['email'], $v['address'], $v['phone'], (int) $v['is_admin'], $id]);
        }
        flash('success', 'User updated.');
        redirect('user_select.php');
    }
}
$page_title = 'Edit user';
include 'header.php';
page_open('Users');
include __DIR__ . '/includes/user_form.php';
page_close();
include 'footer.php';
