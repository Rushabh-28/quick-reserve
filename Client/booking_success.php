<?php
require_once __DIR__ . '/includes/client.php';
$g = require_guest();
$ref = (string) ($_GET['ref'] ?? '');
$b = db_one('SELECT b.*, h.Hotel_name, h.Hotel_city, h.Hotel_address, h.Hotel_phone_no, rt.Room_type_name, r.Room_no FROM booking b JOIN room r ON r.Room_id = b.Room_id
              JOIN hotel h ON h.Hotel_id = r.Hotel_id JOIN room_type rt ON rt.Room_type_id = r.Room_type_id WHERE b.Booking_ref = ? AND b.User_id = ?', 'si', [$ref, $g['User_id']]);
if (!$b || $b['Payment_status'] !== 'paid') { redirect('my_bookings.php'); }
$auto = isset($_GET['paid']);
$invoiceUrl = 'invoice.php?ref=' . urlencode($ref) . '&download=1';
client_header('Booking confirmed');
?>
<div class="page wrap" style="max-width:760px">
  <div class="card success-hero">
    <div class="tick">✓</div>
    <h1>Payment successful!</h1>
    <p class="muted">Your booking <strong><?php echo h($b['Booking_ref']); ?></strong> is confirmed. A GST invoice is downloading now.</p>
    <p><a class="btn btn-primary" href="<?php echo h($invoiceUrl); ?>">⬇ Download invoice (PDF)</a>
       <a class="btn btn-ghost" href="invoice.php?ref=<?php echo urlencode($ref); ?>" target="_blank" rel="noopener">View invoice</a></p>
  </div>
  <div class="card card-body" style="margin-top:20px">
    <h3><?php echo h($b['Hotel_name']); ?>, <?php echo h($b['Hotel_city']); ?></h3>
    <table class="sum">
      <tr><td>Room</td><td><?php echo h($b['Room_type_name']); ?> · Room <?php echo h($b['Room_no']); ?></td></tr>
      <tr><td>Check-in</td><td><?php echo h(date('D, d M Y', strtotime($b['Check_in_date']))); ?> from 12:00</td></tr>
      <tr><td>Check-out</td><td><?php echo h(date('D, d M Y', strtotime($b['Check_out_date']))); ?> until 11:00</td></tr>
      <tr><td>Address</td><td style="white-space:normal"><?php echo h($b['Hotel_address']); ?></td></tr>
      <tr><td>Hotel phone</td><td><?php echo h($b['Hotel_phone_no']); ?></td></tr>
      <tr><td>Invoice no.</td><td><?php echo h($b['Invoice_no']); ?></td></tr>
      <tr class="total"><td>Total paid</td><td><?php echo inr2($b['Total_price']); ?></td></tr>
    </table>
  </div>
  <p style="text-align:center;margin-top:20px"><a class="btn btn-ghost" href="my_bookings.php">Go to my bookings</a> <a class="btn btn-ghost" href="hotels.php">Keep exploring</a></p>
</div>
<?php if ($auto) : ?>
<script>
// Billing is complete: start the invoice download automatically.
setTimeout(function () {
  var f = document.createElement('iframe'); f.style.display = 'none'; f.src = <?php echo json_encode($invoiceUrl); ?>; document.body.appendChild(f);
}, 700);
</script>
<?php endif; ?>
<?php client_footer();
