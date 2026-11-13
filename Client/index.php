<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();
[$in, $out] = search_dates();
$guests = max(1, min(6, (int) ($_GET['guests'] ?? 2)));
$cities = array_column(db_all("SELECT DISTINCT Hotel_city FROM hotel WHERE Hotel_city <> '' ORDER BY Hotel_city"), 'Hotel_city');

$featured = db_all("SELECT h.*, MIN(rt.Room_type_price_per_night) minp,
        (SELECT COALESCE(AVG(Rating),0) FROM review WHERE Hotel_id = h.Hotel_id) avg_rating,
        (SELECT COUNT(*) FROM review WHERE Hotel_id = h.Hotel_id) review_count
      FROM hotel h JOIN room r ON r.Hotel_id = h.Hotel_id AND r.Room_availability = 'yes'
      JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
     GROUP BY h.Hotel_id ORDER BY review_count DESC, h.Hotel_rating DESC LIMIT 4");
$reviews = db_all("SELECT rv.Rating, rv.Comment, u.User_name, h.Hotel_name FROM review rv
      JOIN `user` u ON u.User_id = rv.User_id JOIN hotel h ON h.Hotel_id = rv.Hotel_id
     WHERE rv.Rating >= 4 AND rv.Comment <> '' ORDER BY rv.Created_at DESC LIMIT 3");
$offers = db_all("SELECT Code, Description FROM coupon WHERE Active = 1 AND (Valid_to IS NULL OR Valid_to >= CURDATE())
                    AND (Valid_from IS NULL OR Valid_from <= CURDATE()) AND (Usage_limit IS NULL OR Used_count < Usage_limit) ORDER BY Coupon_id LIMIT 3");

client_header('Book hotels across India');
?>
<section class="hero">
  <div class="wrap">
    <h1>Find your next stay, book in under a minute.</h1>
    <p>Beach resorts, heritage palaces, mountain cottages and city suites. Pay securely with UPI or card and get your GST invoice instantly.</p>
    <?php include __DIR__ . '/includes/search_form_hero.php'; ?>
  </div>
</section>

<?php if ($offers) : ?>
<section class="wrap" style="margin-top:-44px;position:relative">
  <div class="card card-body" style="display:flex;gap:18px;flex-wrap:wrap;align-items:center">
    <strong>🎟️ Offers:</strong>
    <?php foreach ($offers as $o) : ?><span><?php echo badge($o['Code'], 'amber'); ?> <small class="muted"><?php echo h($o['Description']); ?></small></span><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section wrap">
  <div class="section-head"><div><h2>Popular stays</h2><p class="muted" style="margin:0">Most loved by our guests</p></div><a href="hotels.php">See all hotels →</a></div>
  <div class="grid grid-4">
    <?php foreach ($featured as $h) { hotel_card($h); } ?>
  </div>
</section>

<section class="section wrap" style="padding-top:0">
  <h2>Browse by city</h2>
  <div class="cities"><?php foreach ($cities as $c) : ?><a href="hotels.php?city=<?php echo urlencode($c); ?>"><?php echo h($c); ?></a><?php endforeach; ?></div>
</section>

<section class="section wrap" style="padding-top:0">
  <div class="features">
    <div class="feature"><div class="ic">⚡</div><h3>Instant confirmation</h3><p class="muted">Rooms are held for you while you pay, so nobody can take them in the meantime.</p></div>
    <div class="feature"><div class="ic">🔒</div><h3>Secure payments</h3><p class="muted">Powered by Razorpay. UPI, cards, net banking and wallets are all supported.</p></div>
    <div class="feature"><div class="ic">🧾</div><h3>GST invoice, automatically</h3><p class="muted">Your PDF tax invoice downloads the moment your payment succeeds.</p></div>
    <div class="feature"><div class="ic">↩️</div><h3>Easy cancellation</h3><p class="muted">Cancel from My bookings any time before check-in and we process your refund.</p></div>
  </div>
</section>

<?php if ($reviews) : ?>
<section class="section wrap" style="padding-top:0">
  <h2>What guests say</h2>
  <div class="grid grid-3">
    <?php foreach ($reviews as $r) : ?>
      <div class="card card-body"><div class="stars"><?php echo stars((float) $r['Rating']); ?></div>
        <p>“<?php echo h($r['Comment']); ?>”</p><small class="muted">— <?php echo h($r['User_name']); ?>, <?php echo h($r['Hotel_name']); ?></small></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php client_footer();
