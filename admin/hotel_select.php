<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT * FROM hotel ORDER BY Hotel_id');
$page_title = 'Hotels';
include 'header.php';
page_open('Hotels', 'Add hotel', 'hotel_insert.php');
card_open('Hotel list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Id</th><th>Image</th><th>Name</th><th>City</th><th>Description</th><th>Phone no</th><th>Email</th><th>Rating</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo (int) $r['Hotel_id']; ?></td>
      <td><?php echo image_tag($r['Hotel_image'], $r['Hotel_name']); ?></td>
      <td><?php echo h($r['Hotel_name']); ?></td>
      <td><?php echo h($r['Hotel_city']); ?></td>
      <td style="max-width:180px;white-space:normal"><?php echo h(mb_strimwidth($r['Hotel_description'], 0, 120, '…')); ?></td>
      <td><?php echo h($r['Hotel_phone_no']); ?></td>
      <td><?php echo h($r['Hotel_email']); ?></td>
      <td><?php echo str_repeat('★', (int) $r['Hotel_rating']); ?></td>
      <td style="white-space:nowrap"><a href="hotel_update.php?id=<?php echo (int) $r['Hotel_id']; ?>">Edit</a> / <?php echo delete_button('hotel_delete.php', (int) $r['Hotel_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="9" class="text-muted">No hotels yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
