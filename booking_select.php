<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_lib.php';
$me = require_admin();
release_expired_holds();

$status = $_GET['status'] ?? '';
$where = in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'], true) ? 'WHERE b.Booking_status = ?' : '';
$rows = db_all('SELECT b.*, u.User_name, r.Room_no, h.Hotel_name
                  FROM booking b
                  JOIN `user` u ON u.User_id = b.User_id
                  JOIN room r ON r.Room_id = b.Room_id
                  JOIN hotel h ON h.Hotel_id = r.Hotel_id ' . $where . '
                 ORDER BY b.Booking_id DESC', $where ? 's' : '', $where ? [$status] : []);
$badge = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'danger', 'completed' => 'info'];
$pbadge = ['unpaid' => 'warning', 'paid' => 'success', 'refund_pending' => 'danger', 'refunded' => 'secondary'];
$page_title = 'Bookings';
include 'header.php';
page_open('Bookings', 'Add booking', 'booking_insert.php');
card_open('Booking list'); ?>
<p>Filter:
  <a href="booking_select.php">All</a> &middot;
  <?php foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $s) : ?>
    <a href="booking_select.php?status=<?php echo $s; ?>"><?php echo ucfirst($s); ?></a> &middot;
  <?php endforeach; ?>
</p>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Ref</th><th>Guest</th><th>Hotel / Room</th><th>Stay</th><th>Guests</th><th>Total (INR)</th><th>Status</th><th>Payment</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) : ?>
    <tr>
      <td><?php echo h($r['Booking_ref']); ?></td>
      <td><?php echo h($r['User_name']); ?></td>
      <td><?php echo h($r['Hotel_name'] . ' / ' . $r['Room_no']); ?></td>
      <td style="white-space:nowrap"><?php echo h($r['Check_in_date']); ?> &rarr; <?php echo h($r['Check_out_date']); ?></td>
      <td><?php echo (int) $r['Guests']; ?></td>
      <td><?php echo h(money($r['Total_price'])); ?></td>
      <td><span class="badge badge-gradient-<?php echo h($badge[$r['Booking_status']] ?? 'secondary'); ?>"><?php echo h(ucfirst($r['Booking_status'])); ?></span></td>
      <td><span class="badge badge-gradient-<?php echo h($pbadge[$r['Payment_status']] ?? 'secondary'); ?>"><?php echo h(str_replace('_', ' ', ucfirst($r['Payment_status']))); ?></span></td>
      <td style="white-space:nowrap">
        <a href="booking_update.php?id=<?php echo (int) $r['Booking_id']; ?>">Edit</a>
        <?php if ($r['Invoice_no']) : ?> / <a href="../Client/invoice.php?ref=<?php echo h($r['Booking_ref']); ?>&amp;download=1">Invoice</a><?php endif; ?>
        / <?php echo delete_button('booking_delete.php', (int) $r['Booking_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="9" class="text-muted">No bookings found.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
