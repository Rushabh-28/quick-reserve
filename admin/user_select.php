<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT User_id, User_name, User_email, User_address, User_phoneno, is_admin FROM `user` ORDER BY User_id');
$page_title = 'Users';
include 'header.php';
page_open('Users', 'Add user', 'user_insert.php');
card_open('User list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Id</th><th>Name</th><th>Email</th><th>Address</th><th>Phone no</th><th>Role</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo (int) $r['User_id']; ?></td>
      <td><?php echo h($r['User_name']); ?></td>
      <td><?php echo h($r['User_email']); ?></td>
      <td><?php echo h($r['User_address']); ?></td>
      <td><?php echo h($r['User_phoneno']); ?></td>
      <td><?php echo $r['is_admin'] ? '<span class="badge badge-gradient-info">Admin</span>' : '<span class="badge badge-gradient-secondary">Guest</span>'; ?></td>
      <td style="white-space:nowrap"><a href="user_update.php?id=<?php echo (int) $r['User_id']; ?>">Edit</a> / <?php echo delete_button('user_delete.php', (int) $r['User_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="7" class="text-muted">No users yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
