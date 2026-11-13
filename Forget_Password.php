<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';
require_once __DIR__ . '/lib/mailer.php';

$error = '';
if (is_post()) {
    csrf_verify();
    $email = post_str('username');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $row = db_one('SELECT User_id FROM `user` WHERE User_email = ? AND is_admin = 1', 's', [$email]);
        if ($row) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            db_exec('UPDATE `user` SET otp = ?, otpused = 0, otp_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE User_id = ?',
                'si', [password_hash($otp, PASSWORD_DEFAULT), $row['User_id']]);
            $sent = send_mail($email, 'Your Quick Reserve password reset code',
                '<h3>Your one-time code is ' . h($otp) . '</h3><p>It expires in 15 minutes. If you did not ask for it, ignore this e-mail.</p>');
            if (!$sent) {
                flash('error', 'The e-mail could not be sent. Check the SMTP settings in config/config.local.php.');
                redirect('Forget_Password.php');
            }
            $_SESSION['reset_user'] = (int) $row['User_id'];
            $_SESSION['reset_tries'] = 0;
        }
        // Same answer whether or not the account exists (no account enumeration).
        flash('info', 'If that email belongs to an admin account, a 6-digit code has been sent.');
        redirect('resetpassword.php');
    }
}

auth_header('Forgot password');
?>
                <form class="pt-3" method="post" novalidate>
                  <?php echo csrf_field(); ?>
                  <?php if ($error) : ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
                  <div class="form-group">
                    <input type="email" class="form-control form-control-lg" placeholder="Your admin email" name="username" required autofocus>
                  </div>
                  <div class="mt-3">
                    <input type="submit" class="btn btn-block w-100 btn-gradient-primary btn-lg font-weight-medium auth-form-btn" value="SEND CODE">
                  </div>
                  <div class="text-center mt-4 font-weight-light"><a href="login.php" class="text-primary">Back to login</a></div>
                </form>
<?php auth_footer();
