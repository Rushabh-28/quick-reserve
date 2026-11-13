<?php /** expects $v, $errors, $isEdit */
card_open($isEdit ? 'Edit coupon' : 'Add coupon'); ?>
<form class="forms-sample" method="post" novalidate>
  <?php echo csrf_field();
  form_input('Code (letters/numbers)', 'code', $v['code'], $errors, 'text', 'maxlength="30" required style="text-transform:uppercase"');
  form_input('Description', 'description', $v['description'], $errors, 'text', 'maxlength="150"');
  form_select('Type', 'type', ['percent' => 'Percent off', 'flat' => 'Flat amount off'], $v['type'], $errors, 'Select type');
  form_input('Value (% or INR)', 'value', $v['value'], $errors, 'number', 'min="0.01" step="0.01" required');
  form_input('Minimum room charges (INR)', 'min_amount', $v['min_amount'], $errors, 'number', 'min="0" step="0.01"');
  form_input('Maximum discount (INR, optional)', 'max_discount', $v['max_discount'], $errors, 'number', 'min="0" step="0.01"');
  form_input('Valid from (optional)', 'valid_from', $v['valid_from'], $errors, 'date');
  form_input('Valid to (optional)', 'valid_to', $v['valid_to'], $errors, 'date');
  form_input('Usage limit (optional)', 'usage_limit', $v['usage_limit'], $errors, 'number', 'min="1"');
  form_select('Active', 'active', ['1' => 'Yes', '0' => 'No'], $v['active'], $errors, 'Select'); ?>
  <button type="submit" class="btn btn-gradient-primary me-2">Save</button>
  <a href="coupon_select.php" class="btn btn-light">Cancel</a>
</form>
<?php card_close();
