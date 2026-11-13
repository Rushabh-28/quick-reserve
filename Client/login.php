<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (guest()) { redirect($next); }

$errors = []; $email = '';
if (is_post()) {
    csrf_verify();
    $email = post_str('email'); $pw = $_POST['password'] ?? '';
    if ($email === '' || $pw === '') { $errors['form'] = 'Enter your email and password.'; }
    else {
        $row = db_one('SELECT User_id, User_password FROM `user` WHERE User_email = ?', 's', [$email]);
        $rehash = false;
        if ($row && verify_password($pw, $row['User_password'], $rehash)) {
            if ($rehash) { db_exec('UPDATE `user` SET User_password = ? WHERE User_id = ?', 'si', [password_hash($pw, PASSWORD_DEFAULT), $row['User_id']]); }
            guest_login((int) $row['User_id']);
            redirect($next);
        }
        sleep(1);
        $errors['form'] = 'Invalid email or password.';
    }
}
auth_card_open('Welcome back', 'Sign in to manage your bookings and invoices.'); ?>
  <form method="post" novalidate>
    <?php echo csrf_field(); ?><input type="hidden" name="next" value="<?php echo h($next); ?>">
    <?php if (isset($errors['form'])) : ?><div class="alert alert-danger"><?php echo h($errors['form']); ?></div><?php endif; ?>
    <?php client_flash(); field('Email', 'email', $email, $errors, 'email', 'required autofocus autocomplete="username"'); field('Password', 'password', '', $errors, 'password', 'required autocomplete="current-password"'); ?>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
  </form>
  <p style="margin-top:16px"><a href="forgot.php">Forgot password?</a> · New here? <a href="register.php?next=<?php echo urlencode($next); ?>">Create an account</a></p>
<?php auth_card_close();
