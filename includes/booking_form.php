<?php /** expects $v, $errors, $isEdit, $guests, $rooms, optional $couponNote */
card_open($isEdit ? 'Edit booking' : 'Add booking'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_select('Guest', 'user_id', $guests, $v['user'], $errors, 'Select guest');
  form_select('Room', 'room_id', $rooms, $v['room'], $errors, 'Select room');
  form_input('Check-in date', 'check_in', $v['in'], $errors, 'date', 'required');
  form_input('Check-out date', 'check_out', $v['out'], $errors, 'date', 'required');
  form_input('Number of guests', 'guests', $v['guests'], $errors, 'number', 'min="1" max="20" required');
  if (!$isEdit) { form_input('Coupon code (optional)', 'coupon', $v['coupon'], $errors, 'text', 'maxlength="30"'); }
  else { echo '<p class="text-muted">Coupon: ' . h($v['coupon'] ?: 'none') . '</p>'; }
  form_select('Status', 'status', ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed'], $v['status'], $errors, 'Select status'); ?>
  <p class="text-muted">The total is calculated automatically: nights &times; price per night, minus the coupon, plus GST.
     Record the money received under <a href="payment_insert.php">Payments</a>; the booking turns <em>paid</em> once payments cover the total.</p>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="booking_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
