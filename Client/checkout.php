<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();

/** Validate the booking request. Returns [hotel, type, in, out, guests, nights, error]. */
function read_request(array $src): array
{
    $hotelId = (int) ($src['hotel'] ?? 0); $typeId = (int) ($src['type'] ?? 0);
    $in = (string) ($src['in'] ?? ''); $out = (string) ($src['out'] ?? ''); $guests = (int) ($src['guests'] ?? 1);
    $hotel = db_one('SELECT * FROM hotel WHERE Hotel_id = ?', 'i', [$hotelId]);
    $type  = db_one('SELECT * FROM room_type WHERE Room_type_id = ?', 'i', [$typeId]);
    $err = '';
    if (!$hotel || !$type) { $err = 'We could not find that room.'; }
    elseif (!valid_date($in) || !valid_date($out) || $out <= $in) { $err = 'Please choose valid check-in and check-out dates.'; }
    elseif ($in < date('Y-m-d')) { $err = 'Check-in cannot be in the past.'; }
    elseif (nights_between($in, $out) > 30) { $err = 'Stays are limited to 30 nights.'; }
    elseif ($guests < 1 || $guests > (int) $type['Room_type_capacity']) { $err = 'This room sleeps up to ' . (int) ($type['Room_type_capacity'] ?? 0) . ' guests.'; }
    $nights = ($err === '') ? nights_between($in, $out) : 0;
    return [$hotel, $type, $in, $out, $guests, $nights, $err];
}

$g = require_guest();
$src = is_post() ? $_POST : $_GET;
[$hotel, $type, $in, $out, $guests, $nights, $error] = read_request($src);
$couponCode = strtoupper(trim((string) ($src['coupon'] ?? '')));
$couponErr = ''; $coupon = null;

if ($error === '' && !find_free_room((int) $hotel['Hotel_id'], (int) $type['Room_type_id'], $in, $out)) {
    $error = 'Sorry, this room type is no longer available for those dates.';
}
if ($error === '' && $couponCode !== '') {
    [$coupon, $couponErr] = coupon_find($couponCode, round((float) $type['Room_type_price_per_night'] * $nights, 2));
}
if ($error !== '') {
    flash('error', $error);
    redirect($hotel ? 'hotel.php?id=' . (int) $hotel['Hotel_id'] : 'hotels.php');
}
$quote = price_quote((float) $type['Room_type_price_per_night'], $nights, $coupon);

if (is_post() && isset($_POST['pay'])) {
    csrf_verify();
    if ($couponCode !== '' && !$coupon) {
        flash('error', $couponErr ?: 'That coupon cannot be used.');
        redirect('checkout.php?' . http_build_query(['hotel' => $hotel['Hotel_id'], 'type' => $type['Room_type_id'], 'in' => $in, 'out' => $out, 'guests' => $guests]));
    }
    // drop this guest's older unpaid holds so rooms are not blocked needlessly
    db_exec("UPDATE booking SET Booking_status = 'cancelled', Hold_expires = NULL WHERE User_id = ? AND Booking_status = 'pending' AND Payment_status = 'unpaid' AND Hold_expires IS NOT NULL", 'i', [$g['User_id']]);
    $booking = null;
    for ($try = 0; $try < 3 && !$booking; $try++) {
        $room = find_free_room((int) $hotel['Hotel_id'], (int) $type['Room_type_id'], $in, $out);
        if (!$room) { break; }
        try {
            $booking = create_booking(['user_id' => (int) $g['User_id'], 'room_id' => $room, 'in' => $in, 'out' => $out, 'guests' => $guests,
                'coupon_code' => $coupon['Code'] ?? '', 'status' => 'pending', 'hold_minutes' => (int) $qr_config['hold_minutes']]);
        } catch (RuntimeException $ex) {
            if (!str_contains($ex->getMessage(), 'just booked')) { flash('error', $ex->getMessage()); redirect('hotel.php?id=' . (int) $hotel['Hotel_id']); }
        }
    }
    if (!$booking) { flash('error', 'Sorry, those rooms were just taken. Please pick other dates.'); redirect('hotel.php?id=' . (int) $hotel['Hotel_id'] . '&in=' . $in . '&out=' . $out); }
    redirect('pay.php?ref=' . urlencode($booking['ref']));
}

client_header('Review your booking');
$qsBack = http_build_query(['id' => $hotel['Hotel_id'], 'in' => $in, 'out' => $out, 'guests' => $guests]);
?>
<div class="page wrap">
  <h1>Review &amp; pay</h1>
  <p class="muted">Step 1 of 2 · Confirm your stay. Your room is held for <?php echo (int) $qr_config['hold_minutes']; ?> minutes once you continue.</p>
  <div class="layout">
    <div class="card card-body">
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <img src="<?php echo h(hotel_img($hotel['Hotel_image'])); ?>" alt="" style="width:200px;height:130px;object-fit:cover;border-radius:10px">
        <div>
          <h2 style="margin-bottom:4px"><?php echo h($hotel['Hotel_name']); ?></h2>
          <p class="muted" style="margin:0">📍 <?php echo h($hotel['Hotel_city']); ?></p>
          <p style="margin:.6em 0 0"><strong><?php echo h($type['Room_type_name']); ?></strong> · up to <?php echo (int) $type['Room_type_capacity']; ?> guests</p>
        </div>
      </div>
      <table class="sum" style="margin-top:18px">
        <tr><td>Check-in</td><td><?php echo h(date('D, d M Y', strtotime($in))); ?> (from 12:00)</td></tr>
        <tr><td>Check-out</td><td><?php echo h(date('D, d M Y', strtotime($out))); ?> (until 11:00)</td></tr>
        <tr><td>Guests</td><td><?php echo $guests; ?></td></tr>
        <tr><td>Guest name</td><td><?php echo h($g['User_name']); ?></td></tr>
      </table>
      <p><a href="hotel.php?<?php echo h($qsBack); ?>">← Change dates or room</a></p>
    </div>
    <aside class="sticky">
      <div class="card card-body">
        <h3>Price details</h3>
        <table class="sum">
          <tr><td><?php echo inr2($quote['price']); ?> × <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></td><td><?php echo inr2($quote['subtotal']); ?></td></tr>
          <?php if ($quote['discount'] > 0) : ?><tr class="minus"><td>Coupon <?php echo h($coupon['Code']); ?></td><td>− <?php echo inr2($quote['discount']); ?></td></tr><?php endif; ?>
          <tr><td>GST (<?php echo rtrim(rtrim(number_format($quote['gst_rate'], 1), '0'), '.'); ?>%)</td><td><?php echo inr2($quote['tax']); ?></td></tr>
          <tr class="total"><td>Total</td><td><?php echo inr2($quote['total']); ?></td></tr>
        </table>
        <form method="get" style="margin-top:14px">
          <?php foreach (['hotel' => $hotel['Hotel_id'], 'type' => $type['Room_type_id'], 'in' => $in, 'out' => $out, 'guests' => $guests] as $k => $val) : ?><input type="hidden" name="<?php echo $k; ?>" value="<?php echo h($val); ?>"><?php endforeach; ?>
          <div class="field" style="margin-bottom:6px"><label for="coupon">Coupon code</label>
            <div style="display:flex;gap:8px"><input id="coupon" name="coupon" value="<?php echo h($couponCode); ?>" placeholder="e.g. WELCOME10" maxlength="30" style="text-transform:uppercase"><button class="btn btn-ghost" type="submit">Apply</button></div>
            <?php if ($couponErr) : ?><span class="err"><?php echo h($couponErr); ?></span><?php elseif ($coupon) : ?><span style="color:var(--ok);font-size:.85rem">✓ Coupon applied. You save <?php echo inr2($quote['discount']); ?></span><?php endif; ?></div>
        </form>
        <form method="post" data-once>
          <?php echo csrf_field(); ?><input type="hidden" name="pay" value="1">
          <?php foreach (['hotel' => $hotel['Hotel_id'], 'type' => $type['Room_type_id'], 'in' => $in, 'out' => $out, 'guests' => $guests, 'coupon' => $coupon['Code'] ?? ''] as $k => $val) : ?><input type="hidden" name="<?php echo $k; ?>" value="<?php echo h($val); ?>"><?php endforeach; ?>
          <button class="btn btn-primary btn-block" type="submit" style="margin-top:12px">Continue to payment →</button>
        </form>
        <p class="muted" style="font-size:.85rem;margin:12px 0 0">Free cancellation until the day before check-in.</p>
      </div>
    </aside>
  </div>
</div>
<?php client_footer();
