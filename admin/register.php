<?php
/**
 * First-run setup: creates the first administrator account.
 * Once an admin exists this page is closed; further admins are added from Users > Add.
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

if (admin_exists()) {
    flash('info', 'An admin account already exists. Please sign in.');
    redirect('login.php');
}

$errors = [];
$v = ['name' => '', 'email' => '', 'address' => '', 'phone' => ''];
if (is_post()) {
    csrf_verify();
    $v = ['name' => post_str('First_name'), 'email' => post_str('email'), 'address' => post_str('address'), 'phone' => post_str('phoneno')];
    $password = $_POST['password'] ?? '';

    if ($v['name'] === '')    { $errors['name'] = 'Name is required'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'A valid email is required'; }
    if ($v['address'] === '') { $errors['address'] = 'Address is required'; }
    if (!valid_phone($v['phone'])) { $errors['phone'] = 'Enter a valid phone number'; }
    if (strlen($password) < 8) { $errors['password'] = 'Password must be at least 8 characters'; }

    if (!$errors) {
        if (db_one('SELECT User_id FROM `user` WHERE User_email = ?', 's', [$v['email']])) {
            $errors['email'] = 'That email is already registered';
        } else {
            db_exec('INSERT INTO `user` (User_name, User_phoneno, User_email, User_password, User_address, is_admin) VALUES (?,?,?,?,?,1)',
                'sssss', [$v['name'], $v['phone'], $v['email'], password_hash($password, PASSWORD_DEFAULT), $v['address']]);
            flash('success', 'Admin account created. You can sign in now.');
            redirect('login.php');
        }
    }
}

auth_header('Create first admin');
?>
                <form class="pt-3" method="post" novalidate>
                  <?php echo csrf_field(); ?>
                  <div class="form-group">
                    <input type="text" name="First_name" class="form-control form-control-lg" placeholder="Full name" value="<?php echo h($v['name']); ?>" required>
                    <?php echo field_error($errors, 'name'); ?>
                  </div>
                  <div class="form-group">
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Email" value="<?php echo h($v['email']); ?>" required>
                    <?php echo field_error($errors, 'email'); ?>
                  </div>
                  <div class="form-group">
                    <input type="text" name="address" class="form-control form-control-lg" placeholder="Address" value="<?php echo h($v['address']); ?>" required>
                    <?php echo field_error($errors, 'address'); ?>
                  </div>
                  <div class="form-group">
                    <input type="tel" name="phoneno" class="form-control form-control-lg" placeholder="Phone no" value="<?php echo h($v['phone']); ?>" required>
                    <?php echo field_error($errors, 'phone'); ?>
                  </div>
                  <div class="form-group">
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Password (min 8 characters)" required>
                    <?php echo field_error($errors, 'password'); ?>
                  </div>
                  <div class="mt-3">
                    <input class="btn btn-block w-100 btn-gradient-primary btn-lg font-weight-medium auth-form-btn" type="submit" value="CREATE ADMIN">
                  </div>
                  <div class="text-center mt-4 font-weight-light"><a href="login.php" class="text-primary">Back to login</a></div>
                </form>
<?php auth_footer();
