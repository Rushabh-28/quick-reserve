<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();
$g = require_guest();
$tab = in_array($_GET['tab'] ?? '', ['past', 'cancelled'], true) ? $_GET['tab'] : 'upcoming';
$all = db_all('SELECT b.*, h.Hotel_id, h.Hotel_name, h.Hotel_city, h.Hotel_image, rt.Room_type_name, r.Room_no,
                      (SELECT Review_id FROM review WHERE Booking_id = b.Booking_id) AS review_id
                 FROM booking b JOIN room r ON r.Room_id = b.Room_id JOIN hotel h ON h.Hotel_id = r.Hotel_id JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
                WHERE b.User_id = ? ORDER BY b.Check_in_date DESC', 'i', [$g['User_id']]);
$today = date('Y-m-d'); $groups = ['upcoming' => [], 'past' => [], 'cancelled' => []];
foreach ($all as $b) {
    if ($b['Booking_status'] === 'cancelled') { $groups['cancelled'][] = $b; }
    elseif ($b['Booking_status'] === 'completed' || $b['Check_out_date'] <= $today) { $groups['past'][] = $b; }
    else { $groups['upcoming'][] = $b; }
}
$statusBadge = ['pending' => ['Awaiting payment', 'amber'], 'confirmed' => ['Confirmed', 'green'], 'completed' => ['Completed', 'blue'], 'cancelled' => ['Cancelled', 'red']];
$payBadge = ['unpaid' => ['Unpaid', 'amber'], 'paid' => ['Paid', 'green'], 'refund_pending' => ['Refund in progress', 'amber'], 'refunded' => ['Refunded', 'gray']];
client_header('My bookings');
?>
<div class="page wrap">
  <h1>My bookings</h1>
  <div class="tabs">
    <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $k => $l) : ?>
      <a class="<?php echo $tab === $k ? 'on' : ''; ?>" href="my_bookings.php?tab=<?php echo $k; ?>"><?php echo $l; ?> (<?php echo count($groups[$k]); ?>)</a>
    <?php endforeach; ?>
  </div>
  <?php if (!$groups[$tab]) : ?>
    <div class="card empty"><h3>Nothing here yet</h3><p>When you book a stay it will show up here.</p><a class="btn btn-primary" href="hotels.php">Find a hotel</a></div>
  <?php endif; ?>
  <?php foreach ($groups[$tab] as $b) :
      [$sl, $sk] = $statusBadge[$b['Booking_status']]; [$pl, $pk] = $payBadge[$b['Payment_status']];
      $holdOpen = $b['Booking_status'] === 'pending' && $b['Payment_status'] === 'unpaid' && $b['Hold_expires'] && strtotime($b['Hold_expires']) > time(); ?>
    <div class="card bk">
      <img src="<?php echo h(hotel_img($b['Hotel_image'])); ?>" alt="" loading="lazy">
      <div>
        <h3 style="margin-bottom:2px"><a href="hotel.php?id=<?php echo (int) $b['Hotel_id']; ?>"><?php echo h($b['Hotel_name']); ?></a> <small class="muted">· <?php echo h($b['Hotel_city']); ?></small></h3>
        <p style="margin:.2em 0"><?php echo h($b['Room_type_name']); ?> · <?php echo (int) $b['Guests']; ?> guest<?php echo $b['Guests'] > 1 ? 's' : ''; ?></p>
        <p style="margin:.2em 0">📅 <?php echo h(date('d M', strtotime($b['Check_in_date']))); ?> → <?php echo h(date('d M Y', strtotime($b['Check_out_date']))); ?> (<?php echo nights_between($b['Check_in_date'], $b['Check_out_date']); ?> nights)</p>
        <p style="margin:.2em 0"><?php echo badge($sl, $sk) . ' ' . badge($pl, $pk); ?> <strong><?php echo inr2($b['Total_price']); ?></strong> <small class="muted">Ref <?php echo h($b['Booking_ref']); ?></small></p>
        <?php if ($b['Payment_status'] === 'refund_pending') : ?><p class="muted" style="font-size:.88rem;margin:.3em 0">Your refund will reach the original payment method in 5–7 working days.</p><?php endif; ?>
      </div>
      <div class="actions">
        <?php if ($holdOpen) : ?><a class="btn btn-primary btn-sm" href="pay.php?ref=<?php echo urlencode($b['Booking_ref']); ?>">Pay now</a><?php endif; ?>
        <?php if ($b['Invoice_no']) : ?><a class="btn btn-ghost btn-sm" href="invoice.php?ref=<?php echo urlencode($b['Booking_ref']); ?>&amp;download=1">⬇ Invoice</a><?php endif; ?>
        <?php if ($b['Payment_status'] === 'paid' && $b['Check_out_date'] <= $today && $b['Booking_status'] !== 'cancelled' && !$b['review_id']) : ?><a class="btn btn-amber btn-sm" href="review.php?ref=<?php echo urlencode($b['Booking_ref']); ?>">★ Write a review</a><?php endif; ?>
        <?php if (booking_can_cancel($b)) : ?>
          <form method="post" action="cancel_booking.php" onsubmit="return confirm('Cancel this booking?<?php echo $b['Payment_status'] === 'paid' ? ' Your payment will be refunded.' : ''; ?>');">
            <?php echo csrf_field(); ?><input type="hidden" name="ref" value="<?php echo h($b['Booking_ref']); ?>"><button class="btn btn-danger btn-sm btn-block" type="submit">Cancel booking</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php client_footer();
