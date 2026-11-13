<?php /** expects $v, $errors, $isEdit, $bookings */
card_open($isEdit ? 'Edit payment' : 'Add payment'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_select('Booking', 'booking_id', $bookings, $v['booking'], $errors, 'Select booking');
  form_input('Amount', 'amount', $v['amount'], $errors, 'number', 'min="0.01" step="0.01" required');
  form_select('Payment method', 'method', ['cash' => 'Cash', 'card' => 'Card', 'upi' => 'UPI', 'netbanking' => 'Net banking', 'razorpay' => 'Razorpay (online)'], $v['method'], $errors, 'Select method');
  form_select('Status', 'status', ['paid' => 'Paid', 'refunded' => 'Refunded', 'failed' => 'Failed'], $v['status'], $errors, 'Select status'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="payment_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
