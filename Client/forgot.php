<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
require_once __DIR__ . '/../Admin/lib/mailer.php';

$error = '';
if (is_post()) {
    csrf_verify();
    $email = post_str('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Enter a valid email address.'; }
    else {
        $row = db_one('SELECT User_id FROM `user` WHERE User_email = ?', 's', [$email]);
        if ($row) {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            db_exec('UPDATE `user` SET otp = ?, otpused = 0, otp_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE User_id = ?', 'si', [password_hash($otp, PASSWORD_DEFAULT), $row['User_id']]);
            if (!send_mail($email, 'Your Quick Reserve password reset code', '<h3>Your one-time code is ' . h($otp) . '</h3><p>It expires in 15 minutes. If you did not request it, ignore this e-mail.</p>')) {
                flash('error', 'We could not send the e-mail right now. Please try again later.');
                redirect('forgot.php');
            }
            $_SESSION['guest_reset_user'] = (int) $row['User_id']; $_SESSION['guest_reset_tries'] = 0;
        }
        flash('info', 'If that email has an account, a 6-digit code is on its way.');
        redirect('reset.php');
    }
}
auth_card_open('Forgot password', 'We will e-mail you a 6-digit code.'); ?>
  <form method="post" novalidate><?php echo csrf_field(); client_flash(); if ($error) { echo '<div class="alert alert-danger">' . h($error) . '</div>'; } ?>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required autofocus></div>
    <button class="btn btn-primary btn-block" type="submit">Send code</button>
  </form>
<?php auth_card_close();
