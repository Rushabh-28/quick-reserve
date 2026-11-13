<?php
/** Validate + collect user form input. Returns [values, errors]. */
function user_collect(bool $isEdit, int $editId = 0): array
{
    $v = [
        'name' => post_str('user_name'), 'email' => post_str('user_email'),
        'address' => post_str('user_address'), 'phone' => post_str('user_phoneno'),
        'is_admin' => post_str('is_admin'),
    ];
    $pw = $_POST['user_password'] ?? '';
    $e = [];
    if ($v['name'] === '')    { $e['user_name'] = 'Name is required'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $e['user_email'] = 'A valid email is required'; }
    if ($v['address'] === '') { $e['user_address'] = 'Address is required'; }
    if (!valid_phone($v['phone'])) { $e['user_phoneno'] = 'Enter a valid phone number'; }
    if (!in_array($v['is_admin'], ['0', '1'], true)) { $e['is_admin'] = 'Choose a role'; }
    if (($pw !== '' || !$isEdit) && strlen($pw) < 8) { $e['user_password'] = 'Password must be at least 8 characters'; }
    if (!isset($e['user_email'])) {
        $dup = db_one('SELECT User_id FROM `user` WHERE User_email = ? AND User_id <> ?', 'si', [$v['email'], $editId]);
        if ($dup) { $e['user_email'] = 'That email is already used by another user'; }
    }
    return [$v, $e, $pw];
}
