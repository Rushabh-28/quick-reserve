<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT r.*, rt.Room_type_name, h.Hotel_name
                  FROM room r
                  JOIN room_type rt ON r.Room_type_id = rt.Room_type_id
                  JOIN hotel h ON r.Hotel_id = h.Hotel_id
                 ORDER BY h.Hotel_name, r.Room_no');
$page_title = 'Rooms';
include 'header.php';
page_open('Rooms', 'Add room', 'room_insert.php');
card_open('Room list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Id</th><th>Image</th><th>Hotel</th><th>Room type</th><th>Room no</th><th>Available</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo (int) $r['Room_id']; ?></td>
      <td><?php echo image_tag($r['Room_image'], 'Room ' . $r['Room_no']); ?></td>
      <td><?php echo h($r['Hotel_name']); ?></td>
      <td><?php echo h($r['Room_type_name']); ?></td>
      <td><?php echo h($r['Room_no']); ?></td>
      <td><?php echo $r['Room_availability'] === 'yes' ? '<span class="badge badge-gradient-success">Yes</span>' : '<span class="badge badge-gradient-danger">No</span>'; ?></td>
      <td style="white-space:nowrap"><a href="room_update.php?id=<?php echo (int) $r['Room_id']; ?>">Edit</a> / <?php echo delete_button('room_delete.php', (int) $r['Room_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="7" class="text-muted">No rooms yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
