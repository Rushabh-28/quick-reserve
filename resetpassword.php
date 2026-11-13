<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

$error = '';
if (is_post()) {
    csrf_verify();
    $userId = (int) ($_SESSION['reset_user'] ?? 0);
    $otp    = post_str('otp');
    $new    = $_POST['newpassword'] ?? '';
    $conf   = $_POST['cpassword'] ?? '';

    // Throttle OTP guessing: max 5 attempts per reset request.
    $_SESSION['reset_tries'] = ($_SESSION['reset_tries'] ?? 0) + 1;

    if ($userId === 0) {
        $error = 'Please request a new code first.';
    } elseif ($_SESSION['reset_tries'] > 5) {
        $error = 'Too many attempts. Request a new code.';
        unset($_SESSION['reset_user']);
    } elseif (strlen($new) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($new !== $conf) {
        $error = 'Passwords do not match.';
    } else {
        $row = db_one('SELECT otp FROM `user` WHERE User_id = ? AND otpused = 0 AND otp_expires > NOW()', 'i', [$userId]);
        if ($row && $row['otp'] && password_verify($otp, $row['otp'])) {
            db_exec('UPDATE `user` SET User_password = ?, otpused = 1, otp = NULL, otp_expires = NULL WHERE User_id = ?',
                'si', [password_hash($new, PASSWORD_DEFAULT), $userId]);
            unset($_SESSION['reset_user'], $_SESSION['reset_tries']);
            flash('success', 'Password reset. Please sign in.');
            redirect('login.php');
        }
        $error = 'The code is wrong or has expired.';
    }
}

auth_header('Reset password');
?>
                <form class="pt-3" method="post" novalidate>
                  <?php echo csrf_field(); ?>
                  <?php if ($error) : ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
                  <div class="form-group">
                    <input type="text" inputmode="numeric" maxlength="6" class="form-control form-control-lg" placeholder="6-digit code from e-mail" name="otp" required autofocus>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" placeholder="New password (min 8 characters)" name="newpassword" required>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" placeholder="Confirm new password" name="cpassword" required>
                  </div>
                  <div class="mt-3">
                    <input type="submit" class="btn btn-block w-100 btn-gradient-primary btn-lg font-weight-medium auth-form-btn" value="RESET PASSWORD">
                  </div>
                  <div class="text-center mt-4 font-weight-light"><a href="Forget_Password.php" class="text-primary">Send a new code</a> &middot; <a href="login.php" class="text-primary">Back to login</a></div>
                </form>
<?php auth_footer();
