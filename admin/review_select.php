<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT rv.*, h.Hotel_name, u.User_name, b.Booking_ref FROM review rv
                  JOIN hotel h ON h.Hotel_id = rv.Hotel_id JOIN `user` u ON u.User_id = rv.User_id JOIN booking b ON b.Booking_id = rv.Booking_id
                 ORDER BY rv.Review_id DESC');
$page_title = 'Reviews';
include 'header.php';
page_open('Guest reviews');
card_open('Reviews'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Date</th><th>Hotel</th><th>Guest</th><th>Rating</th><th>Comment</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td style="white-space:nowrap"><?php echo h(substr($r['Created_at'], 0, 10)); ?></td>
      <td><?php echo h($r['Hotel_name']); ?></td>
      <td><?php echo h($r['User_name']); ?><br><small class="text-muted"><?php echo h($r['Booking_ref']); ?></small></td>
      <td style="white-space:nowrap"><?php echo str_repeat('★', (int) $r['Rating']) . str_repeat('☆', 5 - (int) $r['Rating']); ?></td>
      <td style="max-width:360px;white-space:normal"><?php echo h($r['Comment']); ?></td>
      <td><?php echo delete_button('review_delete.php', (int) $r['Review_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="6" class="text-muted">No reviews yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
