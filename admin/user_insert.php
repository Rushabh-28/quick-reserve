<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/user_save.php';
$me = require_admin();

$isEdit = false;
$v = ['name' => '', 'email' => '', 'address' => '', 'phone' => '', 'is_admin' => '0'];
$errors = [];
if (is_post()) {
    csrf_verify();
    [$v, $errors, $pw] = user_collect(false);
    if (!$errors) {
        db_exec('INSERT INTO `user` (User_name, User_phoneno, User_email, User_password, User_address, is_admin) VALUES (?,?,?,?,?,?)',
            'sssssi', [$v['name'], $v['phone'], $v['email'], password_hash($pw, PASSWORD_DEFAULT), $v['address'], (int) $v['is_admin']]);
        flash('success', 'User added.');
        redirect('user_select.php');
    }
}
$page_title = 'Add user';
include 'header.php';
page_open('Users');
include __DIR__ . '/includes/user_form.php';
page_close();
include 'footer.php';
