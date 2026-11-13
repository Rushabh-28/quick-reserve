<?php /** expects $v, $errors, $isEdit, $image */
card_open($isEdit ? 'Edit hotel' : 'Add hotel'); ?>
<form class="forms-sample" method="post" enctype="multipart/form-data" novalidate>
  <?php echo csrf_field();
  form_input('Hotel name', 'hotel_name', $v['name'], $errors, 'text', 'required');
  form_input('Hotel email', 'hotel_email', $v['email'], $errors, 'email', 'required');
  form_input('City', 'hotel_city', $v['city'], $errors, 'text', 'required maxlength="80"');
  form_input('Hotel address', 'hotel_address', $v['address'], $errors, 'text', 'required');
  form_input('Hotel phone no', 'hotel_phoneno', $v['phone'], $errors, 'tel', 'required');
  form_input('Amenities (comma separated)', 'hotel_amenities', $v['amenities'], $errors, 'text', 'placeholder="Wi-Fi, Swimming Pool, Spa" maxlength="500"');
  form_select('Star rating', 'rating', ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'], $v['rating'], $errors, 'Select rating');
  form_image('Hotel image', 'image', $image, $errors);
  form_textarea('Hotel description', 'hotel_description', $v['description'], $errors, 8); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="hotel_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
