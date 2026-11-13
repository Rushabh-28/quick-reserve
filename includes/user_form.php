<?php /** expects $v, $errors, $isEdit, $selfId */
card_open($isEdit ? 'Edit user' : 'Add user'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_input('Name', 'user_name', $v['name'], $errors, 'text', 'required');
  form_input('Email', 'user_email', $v['email'], $errors, 'email', 'required');
  form_input($isEdit ? 'New password (leave blank to keep the current one)' : 'Password (min 8 characters)', 'user_password', '', $errors, 'password', $isEdit ? 'autocomplete="new-password"' : 'required autocomplete="new-password"');
  form_input('Address', 'user_address', $v['address'], $errors, 'text', 'required');
  form_input('Phone no', 'user_phoneno', $v['phone'], $errors, 'tel', 'required');
  form_select('Role', 'is_admin', ['1' => 'Admin (can sign in to this panel)', '0' => 'Guest (customer only)'], $v['is_admin'], $errors, 'Select role'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="user_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
