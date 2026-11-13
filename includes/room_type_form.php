<?php /** expects $v, $errors, $isEdit */
card_open($isEdit ? 'Edit room type' : 'Add room type'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_input('Name', 'room_type_name', $v['name'], $errors, 'text', 'required');
  form_textarea('Description', 'room_type_description', $v['description'], $errors, 4);
  form_input('Capacity (guests)', 'room_type_capacity', $v['capacity'], $errors, 'number', 'min="1" max="20" required');
  form_input('Price per night', 'room_type_pricepernight', $v['price'], $errors, 'number', 'min="0" step="0.01" required'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="room_type_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
