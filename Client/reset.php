<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
$error = '';
if (is_post()) {
    csrf_verify();
    $uid = (int) ($_SESSION['guest_reset_user'] ?? 0);
    $otp = post_str('otp'); $new = $_POST['password'] ?? '';
    $_SESSION['guest_reset_tries'] = ($_SESSION['guest_reset_tries'] ?? 0) + 1;
    if ($uid === 0) { $error = 'Please request a new code first.'; }
    elseif ($_SESSION['guest_reset_tries'] > 5) { $error = 'Too many attempts. Request a new code.'; unset($_SESSION['guest_reset_user']); }
    elseif (strlen($new) < 8) { $error = 'Password must be at least 8 characters.'; }
    else {
        $row = db_one('SELECT otp FROM `user` WHERE User_id = ? AND otpused = 0 AND otp_expires > NOW()', 'i', [$uid]);
        if ($row && $row['otp'] && password_verify($otp, $row['otp'])) {
            db_exec('UPDATE `user` SET User_password = ?, otpused = 1, otp = NULL, otp_expires = NULL WHERE User_id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            unset($_SESSION['guest_reset_user'], $_SESSION['guest_reset_tries']);
            flash('success', 'Password changed. Please sign in.');
            redirect('login.php');
        }
        $error = 'That code is wrong or has expired.';
    }
}
auth_card_open('Reset password'); ?>
  <form method="post" novalidate><?php echo csrf_field(); client_flash(); if ($error) { echo '<div class="alert alert-danger">' . h($error) . '</div>'; } ?>
    <div class="field"><label for="otp">6-digit code</label><input id="otp" name="otp" inputmode="numeric" maxlength="6" required autofocus></div>
    <div class="field"><label for="password">New password</label><input id="password" type="password" name="password" required autocomplete="new-password"></div>
    <button class="btn btn-primary btn-block" type="submit">Change password</button>
  </form>
<?php auth_card_close();
