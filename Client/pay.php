<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();
$g = require_guest();
$ref = (string) ($_GET['ref'] ?? '');
$b = db_one('SELECT b.*, h.Hotel_name, h.Hotel_city, rt.Room_type_name FROM booking b JOIN room r ON r.Room_id = b.Room_id JOIN hotel h ON h.Hotel_id = r.Hotel_id
              JOIN room_type rt ON rt.Room_type_id = r.Room_type_id WHERE b.Booking_ref = ? AND b.User_id = ?', 'si', [$ref, $g['User_id']]);
if (!$b) { flash('error', 'Booking not found.'); redirect('my_bookings.php'); }
if ($b['Payment_status'] === 'paid') { redirect('booking_success.php?ref=' . urlencode($ref)); }
if ($b['Booking_status'] !== 'pending' || $b['Payment_status'] !== 'unpaid') { flash('error', 'This booking can no longer be paid online.'); redirect('my_bookings.php'); }
if (!$b['Hold_expires'] || strtotime($b['Hold_expires']) < time()) {
    flash('error', 'Your room hold expired. Please search again to rebook.'); redirect('hotel.php?id=' . (int) db_one('SELECT Hotel_id FROM room WHERE Room_id = ?', 'i', [$b['Room_id']])['Hotel_id']);
}

// Reuse the open gateway order, or create a fresh one (first visit, or after a failed attempt).
$pay = db_one("SELECT * FROM payment WHERE Booking_id = ? AND Status = 'created' ORDER BY Payment_id DESC LIMIT 1", 'i', [(int) $b['Booking_id']]);
if (!$pay) {
    try {
        $order = razorpay_create_order((float) $b['Total_price'], $b['Booking_ref']);
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage()); redirect('my_bookings.php');
    }
    db_exec("INSERT INTO payment (Booking_id, Amount, Payment_method, Status, Gateway_order_id) VALUES (?,?,'razorpay','created',?)", 'ids', [(int) $b['Booking_id'], (float) $b['Total_price'], $order['id']]);
    $pay = db_one('SELECT * FROM payment WHERE Payment_id = ?', 'i', [db_insert_id()]);
}
$live = razorpay_live();
$expires = strtotime($b['Hold_expires']);
client_header('Payment');
?>
<div class="page wrap">
  <h1>Complete your payment</h1>
  <p class="muted">Step 2 of 2 · Room held for <span class="timer" data-expires="<?php echo $expires; ?>">--:--</span></p>
  <div class="layout">
    <div class="card card-body">
      <h3>Booking <?php echo h($b['Booking_ref']); ?></h3>
      <table class="sum">
        <tr><td>Hotel</td><td><?php echo h($b['Hotel_name'] . ', ' . $b['Hotel_city']); ?></td></tr>
        <tr><td>Room</td><td><?php echo h($b['Room_type_name']); ?></td></tr>
        <tr><td>Dates</td><td><?php echo h(date('d M', strtotime($b['Check_in_date']))); ?> → <?php echo h(date('d M Y', strtotime($b['Check_out_date']))); ?></td></tr>
        <tr><td>Guests</td><td><?php echo (int) $b['Guests']; ?></td></tr>
        <?php if ((float) $b['Discount'] > 0) : ?><tr class="minus"><td>Discount (<?php echo h($b['Coupon_code']); ?>)</td><td>− <?php echo inr2($b['Discount']); ?></td></tr><?php endif; ?>
        <tr><td>GST</td><td><?php echo inr2($b['Tax']); ?></td></tr>
        <tr class="total"><td>Amount to pay</td><td><?php echo inr2($b['Total_price']); ?></td></tr>
      </table>
    </div>
    <aside class="sticky">
      <div class="card card-body">
        <?php if ($live) : ?>
          <h3>Pay securely</h3>
          <p class="muted">UPI, cards, net banking and wallets via Razorpay.</p>
          <button class="btn btn-primary btn-block" id="rzp-btn" data-pay-btn type="button">Pay <?php echo inr2($b['Total_price']); ?></button>
        <?php else : ?>
          <h3>Demo payment</h3>
          <div class="demo-note">Razorpay keys are not configured, so this is the <strong>demo gateway</strong>. No real money moves. Add your keys in <code>config/config.local.php</code> to go live.</div>
          <form method="post" action="payment_verify.php" data-once style="margin-top:14px">
            <?php echo csrf_field(); ?><input type="hidden" name="razorpay_order_id" value="<?php echo h($pay['Gateway_order_id']); ?>"><input type="hidden" name="ref" value="<?php echo h($ref); ?>">
            <button class="btn btn-primary btn-block" name="demo_action" value="success" data-pay-btn type="submit">Pay <?php echo inr2($b['Total_price']); ?> (simulate success)</button>
            <button class="btn btn-danger btn-block" name="demo_action" value="fail" type="submit" style="margin-top:8px">Simulate failed payment</button>
          </form>
        <?php endif; ?>
        <p class="muted" style="font-size:.85rem;margin:12px 0 0">🔒 Your invoice downloads automatically after payment.</p>
      </div>
    </aside>
  </div>
</div>
<?php if ($live) : ?>
<form id="verify-form" method="post" action="payment_verify.php" style="display:none">
  <?php echo csrf_field(); ?>
  <input name="ref" value="<?php echo h($ref); ?>">
  <input name="razorpay_order_id" id="v-order"><input name="razorpay_payment_id" id="v-pay"><input name="razorpay_signature" id="v-sig"><input name="failed" id="v-failed">
</form>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
  var options = {
    key: <?php echo json_encode($qr_config['razorpay_key_id'], JSON_HEX_TAG | JSON_HEX_AMP); ?>,
    amount: <?php echo (int) round((float) $b['Total_price'] * 100); ?>,
    currency: "INR",
    name: "Quick Reserve",
    description: <?php echo json_encode($b['Hotel_name'] . ' · ' . $b['Booking_ref'], JSON_HEX_TAG | JSON_HEX_AMP); ?>,
    image: new URL("../Admin/assets/images/favicon.png", location.href).href,
    order_id: <?php echo json_encode($pay['Gateway_order_id']); ?>,
    prefill: { name: <?php echo json_encode($g['User_name'], JSON_HEX_TAG | JSON_HEX_AMP); ?>, email: <?php echo json_encode($g['User_email'], JSON_HEX_TAG | JSON_HEX_AMP); ?>, contact: <?php echo json_encode($g['User_phoneno'], JSON_HEX_TAG | JSON_HEX_AMP); ?> },
    theme: { color: "#0b5d63" },
    handler: function (r) {
      document.getElementById('v-order').value = r.razorpay_order_id;
      document.getElementById('v-pay').value = r.razorpay_payment_id;
      document.getElementById('v-sig').value = r.razorpay_signature;
      document.getElementById('verify-form').submit();
    }
  };
  var rzp = new Razorpay(options);
  rzp.on('payment.failed', function (resp) {
    document.getElementById('v-order').value = options.order_id;
    document.getElementById('v-failed').value = (resp.error && resp.error.description) || 'Payment failed';
    document.getElementById('verify-form').submit();
  });
  document.getElementById('rzp-btn').addEventListener('click', function (e) { rzp.open(); e.preventDefault(); });
})();
</script>
<?php endif; ?>
<?php client_footer();
