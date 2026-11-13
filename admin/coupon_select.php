<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/booking_lib.php';
$me = require_admin();
$rows = db_all('SELECT * FROM coupon ORDER BY Active DESC, Coupon_id DESC');
$page_title = 'Coupons';
include 'header.php';
page_open('Coupons', 'Add coupon', 'coupon_insert.php');
card_open('Coupon list'); ?>
<div class="table-responsive"><table class="table table-striped">
  <thead><tr><th>Code</th><th>Offer</th><th>Min. charges</th><th>Valid</th><th>Used</th><th>Active</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r) :
      $offer = $r['Type'] === 'percent' ? rtrim(rtrim($r['Value'], '0'), '.') . '% off' : 'INR ' . money($r['Value']) . ' off';
      if ($r['Max_discount'] !== null) { $offer .= ' (max ' . money($r['Max_discount']) . ')'; } ?>
    <tr>
      <td><strong><?php echo h($r['Code']); ?></strong><br><small class="text-muted"><?php echo h($r['Description']); ?></small></td>
      <td><?php echo h($offer); ?></td>
      <td><?php echo h(money($r['Min_amount'])); ?></td>
      <td style="white-space:nowrap"><?php echo h($r['Valid_from'] ?: 'any'); ?> &rarr; <?php echo h($r['Valid_to'] ?: 'any'); ?></td>
      <td><?php echo (int) $r['Used_count'] . ($r['Usage_limit'] ? ' / ' . (int) $r['Usage_limit'] : ''); ?></td>
      <td><?php echo $r['Active'] ? '<span class="badge badge-gradient-success">Yes</span>' : '<span class="badge badge-gradient-secondary">No</span>'; ?></td>
      <td style="white-space:nowrap"><a href="coupon_update.php?id=<?php echo (int) $r['Coupon_id']; ?>">Edit</a> / <?php echo delete_button('coupon_delete.php', (int) $r['Coupon_id']); ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) : ?><tr><td colspan="7" class="text-muted">No coupons yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php card_close(); page_close(); include 'footer.php';
