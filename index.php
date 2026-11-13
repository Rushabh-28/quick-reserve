<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_lib.php';
$me = require_admin();
release_expired_holds();

$users    = (int) db_one("SELECT COUNT(*) c FROM `user` WHERE is_admin = 0")['c'];
$hotels   = (int) db_one('SELECT COUNT(*) c FROM hotel')['c'];
$rooms    = (int) db_one('SELECT COUNT(*) c FROM room')['c'];
$bookings = (int) db_one("SELECT COUNT(*) c FROM booking WHERE Booking_status <> 'cancelled'")['c'];
$revenue  = (float) db_one("SELECT COALESCE(SUM(Amount),0) s FROM payment WHERE Status = 'paid'")['s'];
$refunds  = (int) db_one("SELECT COUNT(*) c FROM booking WHERE Payment_status = 'refund_pending'")['c'];
$inhouse  = (int) db_one("SELECT COUNT(*) c FROM booking WHERE Booking_status = 'confirmed' AND Check_in_date <= CURDATE() AND Check_out_date > CURDATE()")['c'];
$arrivals = (int) db_one("SELECT COUNT(*) c FROM booking WHERE Booking_status = 'confirmed' AND Check_in_date = CURDATE()")['c'];

$monthly = db_all("SELECT DATE_FORMAT(Payment_date, '%Y-%m') ym, SUM(Amount) s FROM payment WHERE Status = 'paid'
                    AND Payment_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym");
$maxMonth = max(array_merge([1], array_map(fn($r) => (float) $r['s'], $monthly)));
$top = db_all("SELECT h.Hotel_name, COUNT(b.Booking_id) n, COALESCE(SUM(b.Total_price),0) s
                 FROM hotel h LEFT JOIN room r ON r.Hotel_id = h.Hotel_id
                 LEFT JOIN booking b ON b.Room_id = r.Room_id AND b.Booking_status <> 'cancelled'
                GROUP BY h.Hotel_id ORDER BY s DESC LIMIT 5");
$recent = db_all("SELECT b.Booking_ref, b.Check_in_date, b.Total_price, b.Booking_status, b.Payment_status, u.User_name, h.Hotel_name
                    FROM booking b JOIN `user` u ON u.User_id = b.User_id JOIN room r ON r.Room_id = b.Room_id JOIN hotel h ON h.Hotel_id = r.Hotel_id
                   ORDER BY b.Booking_id DESC LIMIT 6");

$page_title = 'Dashboard';
include 'header.php';
$cards = [
    ['Guests',             $users,               'bg-gradient-danger'],
    ['Hotels',             $hotels,              'bg-gradient-info'],
    ['Rooms',              $rooms,               'bg-gradient-primary'],
    ['Active bookings',    $bookings,            'bg-gradient-success'],
    ['Revenue (INR)',      money($revenue),      'bg-gradient-warning'],
    ['In-house / arriving today', $inhouse . ' / ' . $arrivals, 'bg-gradient-info'],
];
?>
<div class="content-wrapper">
  <div class="page-header">
    <h3 class="page-title"><span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-home"></i></span> Dashboard</h3>
  </div>
  <?php if ($refunds) : ?>
  <div class="alert alert-warning"><?php echo $refunds; ?> cancelled booking(s) are waiting for a refund. <a href="booking_select.php?status=cancelled">Review them</a>.</div>
  <?php endif; ?>
  <div class="row">
    <?php foreach ($cards as [$label, $value, $cls]) : ?>
    <div class="col-md-4 stretch-card grid-margin">
      <div class="card <?php echo $cls; ?> card-img-holder text-white">
        <div class="card-body">
          <img src="assets/images/dashboard/circle.svg" class="card-img-absolute" alt="">
          <h4 class="font-weight-normal mb-3"><?php echo h($label); ?></h4>
          <h2 class="mb-4"><?php echo h($value); ?></h2>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="row">
    <div class="col-lg-5 grid-margin stretch-card"><div class="card"><div class="card-body">
      <h4 class="card-title">Revenue, last 6 months</h4>
      <?php if (!$monthly) : ?><p class="text-muted">No payments yet.</p><?php endif; ?>
      <?php foreach ($monthly as $m) : $pct = max(3, round((float) $m['s'] / $maxMonth * 100)); ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between"><span><?php echo h(date('M Y', strtotime($m['ym'] . '-01'))); ?></span><strong><?php echo h(money($m['s'])); ?></strong></div>
          <div class="progress"><div class="progress-bar bg-gradient-primary" style="width:<?php echo $pct; ?>%"></div></div>
        </div>
      <?php endforeach; ?>
      <h4 class="card-title mt-4">Top hotels by sales</h4>
      <?php foreach ($top as $t) : ?>
        <div class="d-flex justify-content-between border-bottom py-2"><span><?php echo h($t['Hotel_name']); ?> <small class="text-muted">(<?php echo (int) $t['n']; ?> bookings)</small></span><strong><?php echo h(money($t['s'])); ?></strong></div>
      <?php endforeach; ?>
    </div></div></div>
    <div class="col-lg-7 grid-margin stretch-card"><div class="card"><div class="card-body">
      <h4 class="card-title">Latest bookings</h4>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Ref</th><th>Guest</th><th>Hotel</th><th>Check-in</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r) : ?>
          <tr><td><?php echo h($r['Booking_ref']); ?></td><td><?php echo h($r['User_name']); ?></td><td><?php echo h($r['Hotel_name']); ?></td>
              <td><?php echo h($r['Check_in_date']); ?></td><td><?php echo h(money($r['Total_price'])); ?></td>
              <td><?php echo h(ucfirst($r['Booking_status'])); ?> / <?php echo h(str_replace('_', ' ', $r['Payment_status'])); ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$recent) : ?><tr><td colspan="6" class="text-muted">No bookings yet.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
      <a href="booking_select.php">All bookings &rarr;</a>
    </div></div></div>
  </div>
</div>
<?php include 'footer.php';
