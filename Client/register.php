<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (guest()) { redirect($next); }

$errors = []; $v = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
if (is_post()) {
    csrf_verify();
    $v = ['name' => post_str('name'), 'email' => post_str('email'), 'phone' => post_str('phone'), 'address' => post_str('address')];
    $pw = $_POST['password'] ?? '';
    if ($v['name'] === '' || strlen($v['name']) > 100) { $errors['name'] = 'Please enter your name'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email'; }
    if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number'; }
    if ($v['address'] === '' || strlen($v['address']) > 255) { $errors['address'] = 'Please enter your address (used on invoices)'; }
    if (strlen($pw) < 8) { $errors['password'] = 'Use at least 8 characters'; }
    if (!$errors && db_one('SELECT User_id FROM `user` WHERE User_email = ?', 's', [$v['email']])) { $errors['email'] = 'An account with this email already exists. Try signing in.'; }
    if (!$errors) {
        db_exec('INSERT INTO `user` (User_name, User_phoneno, User_email, User_password, User_address, is_admin) VALUES (?,?,?,?,?,0)',
            'sssss', [$v['name'], $v['phone'], $v['email'], password_hash($pw, PASSWORD_DEFAULT), $v['address']]);
        guest_login(db_insert_id());
        flash('success', 'Welcome to Quick Reserve! Your account is ready.');
        redirect($next);
    }
}
auth_card_open('Create your account', 'Takes 30 seconds. Your details are used for bookings and GST invoices.'); ?>
  <form method="post" novalidate>
    <?php echo csrf_field(); ?><input type="hidden" name="next" value="<?php echo h($next); ?>">
    <?php field('Full name', 'name', $v['name'], $errors, 'text', 'required autofocus autocomplete="name"');
    field('Email', 'email', $v['email'], $errors, 'email', 'required autocomplete="email"');
    field('Phone', 'phone', $v['phone'], $errors, 'tel', 'required autocomplete="tel"');
    field('Address', 'address', $v['address'], $errors, 'text', 'required autocomplete="street-address"');
    field('Password (min 8 characters)', 'password', '', $errors, 'password', 'required autocomplete="new-password"'); ?>
    <button class="btn btn-primary btn-block" type="submit">Create account</button>
  </form>
  <p style="margin-top:16px">Already registered? <a href="login.php?next=<?php echo urlencode($next); ?>">Sign in</a></p>
<?php auth_card_close();
