<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
$me = require_admin();
$rows = db_all('SELECT p.*, u.User_name FROM payment p
                  JOIN booking b ON b.Booking_id = p.Booking_id
                  JOIN `user` u ON u.User_id = b.User_id
                 ORDER BY p.Payment_id DESC');
$page_title = 'Payments';
include 'header.php';
page_open('Payments', 'Add payment', 'payment_insert.php');
card_open('Payment list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Id</th><th>Booking</th><th>Guest</th><th>Amount</th><th>Payment date</th><th>Method</th><th>Status</th><th>Gateway ref</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo (int) $r['Payment_id']; ?></td>
      <td>#<?php echo (int) $r['Booking_id']; ?></td>
      <td><?php echo h($r['User_name']); ?></td>
      <td><?php echo h(number_format((float) $r['Amount'], 2)); ?></td>
      <td><?php echo h($r['Payment_date']); ?></td>
      <td><?php echo h(ucfirst($r['Payment_method'])); ?></td>
      <td><?php echo h(ucfirst($r['Status'])); ?></td>
      <td><small><?php echo h($r['Gateway_payment_id'] ?? '-'); ?></small></td>
      <td style="white-space:nowrap"><a href="payment_update.php?id=<?php echo (int) $r['Payment_id']; ?>">Edit</a> / <?php echo delete_button('payment_delete.php', (int) $r['Payment_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="9" class="text-muted">No payments yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
