<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();
$id = (int) ($_GET['id'] ?? 0);
$hotel = db_one('SELECT * FROM hotel WHERE Hotel_id = ?', 'i', [$id]);
if (!$hotel) { http_response_code(404); client_header('Hotel not found'); echo '<div class="page wrap"><div class="card empty"><h2>We could not find that hotel</h2><a class="btn btn-primary" href="hotels.php">Browse hotels</a></div></div>'; client_footer(); exit; }

[$in, $out, $hasDates] = search_dates();
$guests = max(1, min(6, (int) ($_GET['guests'] ?? 2)));
$nights = nights_between($in, $out);
$cities = array_column(db_all("SELECT DISTINCT Hotel_city FROM hotel WHERE Hotel_city <> '' ORDER BY Hotel_city"), 'Hotel_city');
$types  = free_rooms_by_type($id, $in, $out);
$stats  = hotel_rating_stats($id);
$reviews = db_all('SELECT rv.*, u.User_name FROM review rv JOIN `user` u ON u.User_id = rv.User_id WHERE rv.Hotel_id = ? ORDER BY rv.Review_id DESC LIMIT 20', 'i', [$id]);
$roomImg = db_one("SELECT Room_image FROM room WHERE Hotel_id = ? AND Room_image IS NOT NULL LIMIT 1", 'i', [$id]);

client_header($hotel['Hotel_name']);
?>
<div class="page wrap">
  <p class="muted"><a href="hotels.php">Hotels</a> › <a href="hotels.php?city=<?php echo urlencode($hotel['Hotel_city']); ?>"><?php echo h($hotel['Hotel_city']); ?></a> › <?php echo h($hotel['Hotel_name']); ?></p>
  <h1><?php echo h($hotel['Hotel_name']); ?> <span class="stars" style="font-size:1rem"><?php echo str_repeat('★', (int) $hotel['Hotel_rating']); ?></span></h1>
  <p class="muted">📍 <?php echo h($hotel['Hotel_address']); ?>
    <?php if ($stats['count']) : ?> · <strong><?php echo number_format($stats['avg'], 1); ?></strong> <span class="stars"><?php echo stars($stats['avg']); ?></span> (<?php echo $stats['count']; ?> review<?php echo $stats['count'] > 1 ? 's' : ''; ?>)<?php endif; ?></p>

  <div class="gallery">
    <img class="main" src="<?php echo h(hotel_img($hotel['Hotel_image'])); ?>" alt="<?php echo h($hotel['Hotel_name']); ?>">
    <img src="<?php echo h(hotel_img($roomImg['Room_image'] ?? $hotel['Hotel_image'])); ?>" alt="Room at <?php echo h($hotel['Hotel_name']); ?>">
  </div>

  <div class="layout">
    <div>
      <h2>About this hotel</h2>
      <p><?php echo nl2br(h($hotel['Hotel_description'])); ?></p>
      <?php $am = amenity_list($hotel['Hotel_amenities']); if ($am) : ?>
        <h3 style="margin-top:22px">Amenities</h3><div class="amen"><?php foreach ($am as $a) : ?><span><?php echo h($a); ?></span><?php endforeach; ?></div>
      <?php endif; ?>

      <h2 id="rooms" style="margin-top:34px">Choose your room</h2>
      <p class="muted"><?php echo h(date('D d M', strtotime($in))); ?> → <?php echo h(date('D d M', strtotime($out))); ?> · <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?> · <?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?></p>
      <?php
      $shown = 0;
      foreach ($types as $t) :
          if ((int) $t['Room_type_capacity'] < $guests) { continue; }
          $shown++;
          $q = price_quote((float) $t['Room_type_price_per_night'], $nights);
          $qsb = http_build_query(['hotel' => $id, 'type' => $t['Room_type_id'], 'in' => $in, 'out' => $out, 'guests' => $guests]); ?>
        <div class="room">
          <img src="<?php echo h(hotel_img($t['room_image'] ?: $hotel['Hotel_image'])); ?>" alt="<?php echo h($t['Room_type_name']); ?>" loading="lazy">
          <div>
            <h3><?php echo h($t['Room_type_name']); ?></h3>
            <p class="muted" style="margin:0 0 6px"><?php echo h($t['Room_type_description']); ?></p>
            <small>👥 Sleeps <?php echo (int) $t['Room_type_capacity']; ?> · <?php echo (int) $t['free_count'] <= 2 ? '<strong style="color:var(--bad)">Only ' . (int) $t['free_count'] . ' left</strong>' : (int) $t['free_count'] . ' available'; ?></small>
          </div>
          <div class="cta">
            <div class="big"><?php echo inr($t['Room_type_price_per_night']); ?></div><small class="muted">per night</small><br>
            <small class="muted"><?php echo inr2($q['total']); ?> total incl. GST</small><br>
            <a class="btn btn-primary btn-sm" style="margin-top:8px" href="checkout.php?<?php echo h($qsb); ?>">Book now</a>
          </div>
        </div>
      <?php endforeach;
      if (!$shown) : ?>
        <div class="card empty"><h3>No rooms available for these dates</h3><p>Try other dates or a smaller group. Sold-out rooms reopen when bookings are cancelled.</p></div>
      <?php endif; ?>

      <h2 id="reviews" style="margin-top:34px">Guest reviews</h2>
      <?php if (!$reviews) : ?><p class="muted">No reviews yet. Be the first to stay and share your experience.</p><?php endif; ?>
      <?php foreach ($reviews as $r) : ?>
        <div class="review"><span class="stars"><?php echo stars((float) $r['Rating']); ?></span> <strong><?php echo h($r['User_name']); ?></strong>
          <small class="muted"> · <?php echo h(date('M Y', strtotime($r['Created_at']))); ?></small>
          <?php if ($r['Comment'] !== '') : ?><p style="margin:.3em 0 0"><?php echo h($r['Comment']); ?></p><?php endif; ?></div>
      <?php endforeach; ?>
    </div>

    <aside class="sticky">
      <form class="card card-body" method="get" action="hotel.php" data-dates>
        <h3>Change dates</h3>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="field"><label for="in">Check-in</label><input id="in" type="date" name="in" value="<?php echo h($in); ?>" required></div>
        <div class="field"><label for="out">Check-out</label><input id="out" type="date" name="out" value="<?php echo h($out); ?>" required></div>
        <div class="field"><label for="g">Guests</label><select id="g" name="guests"><?php for ($i = 1; $i <= 6; $i++) : ?><option value="<?php echo $i; ?>"<?php echo $i === $guests ? ' selected' : ''; ?>><?php echo $i; ?></option><?php endfor; ?></select></div>
        <button class="btn btn-primary btn-block" type="submit">Check availability</button>
      </form>
      <div class="card card-body" style="margin-top:16px"><h3>Contact</h3>
        <p style="margin:0">📞 <?php echo h($hotel['Hotel_phone_no']); ?><br>✉️ <?php echo h($hotel['Hotel_email']); ?></p></div>
    </aside>
  </div>
</div>
<?php client_footer();
