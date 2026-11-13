<?php
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/includes/auth_card.php';
$g = require_guest();
$ref = (string) ($_GET['ref'] ?? $_POST['ref'] ?? '');
$b = db_one('SELECT b.*, r.Hotel_id, h.Hotel_name FROM booking b JOIN room r ON r.Room_id = b.Room_id JOIN hotel h ON h.Hotel_id = r.Hotel_id
              WHERE b.Booking_ref = ? AND b.User_id = ?', 'si', [$ref, $g['User_id']]);
if (!$b || $b['Payment_status'] !== 'paid' || $b['Booking_status'] === 'cancelled' || $b['Check_out_date'] > date('Y-m-d')) {
    flash('error', 'You can review a stay after check-out.'); redirect('my_bookings.php');
}
if (db_one('SELECT Review_id FROM review WHERE Booking_id = ?', 'i', [$b['Booking_id']])) { flash('info', 'You have already reviewed this stay. Thank you!'); redirect('my_bookings.php?tab=past'); }

$error = ''; $rating = 0; $comment = '';
if (is_post()) {
    csrf_verify();
    $rating = (int) ($_POST['rating'] ?? 0); $comment = mb_substr(post_str('comment'), 0, 1000);
    if ($rating < 1 || $rating > 5) { $error = 'Please choose a star rating.'; }
    else {
        db_exec('INSERT INTO review (Hotel_id, User_id, Booking_id, Rating, Comment) VALUES (?,?,?,?,?)', 'iiiis', [$b['Hotel_id'], $g['User_id'], $b['Booking_id'], $rating, $comment]);
        flash('success', 'Thanks for your review!');
        redirect('hotel.php?id=' . (int) $b['Hotel_id'] . '#reviews');
    }
}
auth_card_open('Rate your stay', $b['Hotel_name']); ?>
  <form method="post" novalidate><?php echo csrf_field(); ?><input type="hidden" name="ref" value="<?php echo h($ref); ?>">
    <?php if ($error) : ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
    <div class="field"><label>Your rating</label>
      <div class="rate-input"><?php for ($i = 5; $i >= 1; $i--) : ?><input type="radio" id="s<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"<?php echo $rating === $i ? ' checked' : ''; ?>><label for="s<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label><?php endfor; ?></div></div>
    <div class="field"><label for="comment">Tell others about your stay (optional)</label><textarea id="comment" name="comment" rows="4" maxlength="1000"><?php echo h($comment); ?></textarea></div>
    <button class="btn btn-primary btn-block" type="submit">Submit review</button>
  </form>
<?php auth_card_close();
