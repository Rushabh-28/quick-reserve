<?php /** expects $v, $errors, $isEdit, $image, $hotels, $types */
card_open($isEdit ? 'Edit room' : 'Add room'); ?>
<form class="forms-sample" method="post" enctype="multipart/form-data" novalidate>
  <?php echo csrf_field();
  form_select('Hotel', 'hotel_name', $hotels, $v['hotel'], $errors, 'Select hotel');
  form_select('Room type', 'room_type', $types, $v['type'], $errors, 'Select room type');
  form_input('Room no', 'room_no', $v['no'], $errors, 'text', 'maxlength="20" required');
  form_image('Room image', 'room_image', $image, $errors);
  form_select('Available for booking', 'room_availability', ['yes' => 'Yes', 'no' => 'No'], $v['availability'], $errors, 'Select'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="room_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
