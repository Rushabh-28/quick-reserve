<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT * FROM room_type ORDER BY Room_type_id');
$page_title = 'Room types';
include 'header.php';
page_open('Room types', 'Add room type', 'room_type_insert.php');
card_open('Room type list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Id</th><th>Name</th><th>Description</th><th>Capacity</th><th>Price per night</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo (int) $r['Room_type_id']; ?></td>
      <td><?php echo h($r['Room_type_name']); ?></td>
      <td style="max-width:300px;white-space:normal"><?php echo h($r['Room_type_description']); ?></td>
      <td><?php echo (int) $r['Room_type_capacity']; ?></td>
      <td><?php echo h(number_format((float) $r['Room_type_price_per_night'], 2)); ?></td>
      <td style="white-space:nowrap"><a href="room_type_update.php?id=<?php echo (int) $r['Room_type_id']; ?>">Edit</a> / <?php echo delete_button('room_type_delete.php', (int) $r['Room_type_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="6" class="text-muted">No room types yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
