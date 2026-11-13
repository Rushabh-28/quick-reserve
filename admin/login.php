<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$email  = '';
if (is_post()) {
    csrf_verify();
    $email    = post_str('U_email');
    $password = $_POST['Password'] ?? '';
    if ($email === '')    { $errors['email'] = 'Email is required'; }
    if ($password === '') { $errors['password'] = 'Password is required'; }

    if (!$errors) {
        $row = db_one('SELECT User_id, User_password FROM `user` WHERE User_email = ? AND is_admin = 1', 's', [$email]);
        $rehash = false;
        if ($row && verify_password($password, $row['User_password'], $rehash)) {
            if ($rehash) {
                db_exec('UPDATE `user` SET User_password = ? WHERE User_id = ?', 'si',
                    [password_hash($password, PASSWORD_DEFAULT), $row['User_id']]);
            }
            login_user((int) $row['User_id']);
            redirect('index.php');
        }
        sleep(1);   // slow down password guessing
        $errors['form'] = 'Invalid email or password.';
    }
}

auth_header('Login');
?>
                <form class="pt-3" method="post" novalidate>
                  <?php echo csrf_field(); ?>
                  <?php if (isset($errors['form'])) : ?><div class="alert alert-danger"><?php echo h($errors['form']); ?></div><?php endif; ?>
                  <div class="form-group">
                    <input type="email" class="form-control form-control-lg" placeholder="Email" name="U_email" value="<?php echo h($email); ?>" required autofocus>
                    <?php echo field_error($errors, 'email'); ?>
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" placeholder="Password" name="Password" required>
                    <?php echo field_error($errors, 'password'); ?>
                  </div>
                  <div class="mt-3">
                    <input type="submit" class="btn btn-block w-100 btn-gradient-primary btn-lg font-weight-medium auth-form-btn" value="LOGIN">
                  </div>
                  <div class="my-2 d-flex justify-content-between align-items-center">
                    <span></span>
                    <a href="Forget_Password.php" class="auth-link text-black">Forgot password?</a>
                  </div>
                  <?php if (!admin_exists()) : ?>
                  <div class="text-center mt-4 font-weight-light">No admin yet? <a href="register.php" class="text-primary">Create the first admin</a></div>
                  <?php endif; ?>
                </form>
<?php auth_footer();
