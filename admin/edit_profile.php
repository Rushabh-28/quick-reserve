<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();

$v = ['name' => $me['User_name'], 'email' => $me['User_email'], 'address' => $me['User_address'], 'phone' => $me['User_phoneno']];
$errors = [];
if (is_post()) {
    csrf_verify();
    $v = ['name' => post_str('user_name'), 'email' => post_str('user_email'), 'address' => post_str('user_address'), 'phone' => post_str('user_phoneno')];
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';

    if ($v['name'] === '') { $errors['user_name'] = 'Name is required'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors['user_email'] = 'A valid email is required'; }
    if ($v['address'] === '') { $errors['user_address'] = 'Address is required'; }
    if (!valid_phone($v['phone'])) { $errors['user_phoneno'] = 'Enter a valid phone number'; }
    if (!isset($errors['user_email']) && db_one('SELECT User_id FROM `user` WHERE User_email = ? AND User_id <> ?', 'si', [$v['email'], $me['User_id']])) {
        $errors['user_email'] = 'That email is already used by another user';
    }
    if ($new !== '') {
        if (strlen($new) < 8) { $errors['new_password'] = 'Password must be at least 8 characters'; }
        if (!verify_password($current, $me['User_password'])) { $errors['current_password'] = 'Current password is incorrect'; }
    }
    if (!$errors) {
        db_exec('UPDATE `user` SET User_name=?, User_email=?, User_address=?, User_phoneno=? WHERE User_id=?',
            'ssssi', [$v['name'], $v['email'], $v['address'], $v['phone'], $me['User_id']]);
        if ($new !== '') {
            db_exec('UPDATE `user` SET User_password=? WHERE User_id=?', 'si', [password_hash($new, PASSWORD_DEFAULT), $me['User_id']]);
        }
        flash('success', 'Profile updated.');
        redirect('edit_profile.php');
    }
}
$page_title = 'Edit profile';
include 'header.php';
page_open('Edit profile');
card_open('Your details'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_input('Name', 'user_name', $v['name'], $errors, 'text', 'required');
  form_input('Email', 'user_email', $v['email'], $errors, 'email', 'required');
  form_input('Address', 'user_address', $v['address'], $errors, 'text', 'required');
  form_input('Phone no', 'user_phoneno', $v['phone'], $errors, 'tel', 'required'); ?>
  <hr>
  <p class="text-muted">To change your password, fill in both fields below. Otherwise leave them empty.</p>
  <?php
  form_input('Current password', 'current_password', '', $errors, 'password', 'autocomplete="current-password"');
  form_input('New password (min 8 characters)', 'new_password', '', $errors, 'password', 'autocomplete="new-password"'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="index.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close(); page_close(); include 'footer.php';
