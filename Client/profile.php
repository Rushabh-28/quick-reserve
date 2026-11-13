<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
$g = require_guest();
$errors = []; $v = ['name' => $g['User_name'], 'phone' => $g['User_phoneno'], 'address' => $g['User_address']];
if (is_post()) {
    csrf_verify();
    $v = ['name' => post_str('name'), 'phone' => post_str('phone'), 'address' => post_str('address')];
    $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? '';
    if ($v['name'] === '') { $errors['name'] = 'Please enter your name'; }
    if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number'; }
    if ($v['address'] === '') { $errors['address'] = 'Please enter your address'; }
    if ($new !== '') {
        if (strlen($new) < 8) { $errors['new_password'] = 'Use at least 8 characters'; }
        if (!verify_password($cur, $g['User_password'])) { $errors['current_password'] = 'Current password is incorrect'; }
    }
    if (!$errors) {
        db_exec('UPDATE `user` SET User_name = ?, User_phoneno = ?, User_address = ? WHERE User_id = ?', 'sssi', [$v['name'], $v['phone'], $v['address'], $g['User_id']]);
        if ($new !== '') { db_exec('UPDATE `user` SET User_password = ? WHERE User_id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $g['User_id']]); }
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}
auth_card_open('My profile', $g['User_email']); ?>
  <form method="post" novalidate><?php echo csrf_field();
    field('Full name', 'name', $v['name'], $errors, 'text', 'required');
    field('Phone', 'phone', $v['phone'], $errors, 'tel', 'required');
    field('Address', 'address', $v['address'], $errors, 'text', 'required'); ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0"><p class="muted">Change password (optional)</p>
    <?php field('Current password', 'current_password', '', $errors, 'password', 'autocomplete="current-password"');
    field('New password', 'new_password', '', $errors, 'password', 'autocomplete="new-password"'); ?>
    <button class="btn btn-primary btn-block" type="submit">Save changes</button>
  </form>
<?php auth_card_close();
