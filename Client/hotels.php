<?php
require_once __DIR__ . '/includes/client.php';
release_expired_holds();
[$in, $out, $hasDates] = search_dates();
$guests = max(1, min(6, (int) ($_GET['guests'] ?? 1)));
$city   = trim((string) ($_GET['city'] ?? ''));
$minStar = (int) ($_GET['stars'] ?? 0);
$maxPrice = (int) ($_GET['max'] ?? 0);
$sort = $_GET['sort'] ?? 'popular';
$cities = array_column(db_all("SELECT DISTINCT Hotel_city FROM hotel WHERE Hotel_city <> '' ORDER BY Hotel_city"), 'Hotel_city');

$where = ["r.Room_availability = 'yes'", 'rt.Room_type_capacity >= ?'];
$types = 'i'; $params = [$guests];
if ($city !== '')   { $where[] = 'h.Hotel_city = ?'; $types .= 's'; $params[] = $city; }
if ($minStar >= 1 && $minStar <= 5) { $where[] = 'h.Hotel_rating >= ?'; $types .= 'i'; $params[] = $minStar; }
if ($maxPrice > 0)  { $where[] = 'rt.Room_type_price_per_night <= ?'; $types .= 'i'; $params[] = $maxPrice; }
if ($hasDates) {
    $where[] = 'NOT EXISTS (SELECT 1 FROM booking b WHERE b.Room_id = r.Room_id AND ' . blocking_sql('b') . ' AND b.Check_in_date < ? AND b.Check_out_date > ?)';
    $types .= 'ss'; $params[] = $out; $params[] = $in;
}
$order = ['price_asc' => 'minp ASC', 'price_desc' => 'minp DESC', 'rating' => 'avg_rating DESC, h.Hotel_rating DESC'][$sort] ?? 'review_count DESC, h.Hotel_rating DESC';
$rows = db_all("SELECT h.*, MIN(rt.Room_type_price_per_night) minp, COUNT(DISTINCT r.Room_id) free_rooms,
        (SELECT COALESCE(AVG(Rating),0) FROM review WHERE Hotel_id = h.Hotel_id) avg_rating,
        (SELECT COUNT(*) FROM review WHERE Hotel_id = h.Hotel_id) review_count
      FROM hotel h JOIN room r ON r.Hotel_id = h.Hotel_id JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
     WHERE " . implode(' AND ', $where) . " GROUP BY h.Hotel_id ORDER BY $order", $types, $params);

$qs = $hasDates ? '&in=' . $in . '&out=' . $out . '&guests=' . $guests : '&guests=' . $guests;
client_header('Hotels' . ($city ? ' in ' . $city : ''));
?>
<div class="page wrap">
  <?php include __DIR__ . '/includes/search_form_compact.php'; ?>
  <h1 style="margin-top:26px"><?php echo $city ? 'Hotels in ' . h($city) : 'All hotels'; ?></h1>
  <p class="muted"><?php echo count($rows); ?> propert<?php echo count($rows) === 1 ? 'y' : 'ies'; ?> found<?php echo $hasDates ? ' for ' . h(date('d M', strtotime($in))) . ' – ' . h(date('d M', strtotime($out))) . ' (' . nights_between($in, $out) . ' nights)' : ''; ?></p>
  <div class="filters">
    <form method="get" class="card card-body" style="position:sticky;top:88px">
      <input type="hidden" name="city" value="<?php echo h($city); ?>">
      <?php if ($hasDates) : ?><input type="hidden" name="in" value="<?php echo h($in); ?>"><input type="hidden" name="out" value="<?php echo h($out); ?>"><?php endif; ?>
      <input type="hidden" name="guests" value="<?php echo $guests; ?>">
      <div class="field"><label>Sort by</label>
        <select name="sort"><?php foreach (['popular' => 'Most popular', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Guest rating'] as $k => $l) : ?><option value="<?php echo $k; ?>"<?php echo $sort === $k ? ' selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Minimum stars</label>
        <select name="stars"><option value="0">Any</option><?php for ($i = 3; $i <= 5; $i++) : ?><option value="<?php echo $i; ?>"<?php echo $minStar === $i ? ' selected' : ''; ?>><?php echo $i; ?>★ and up</option><?php endfor; ?></select></div>
      <div class="field"><label>Max price per night (₹)</label><input type="number" name="max" min="500" step="100" value="<?php echo $maxPrice ?: ''; ?>" placeholder="No limit"></div>
      <button class="btn btn-primary btn-block" type="submit">Apply filters</button>
      <a class="btn btn-ghost btn-block" style="margin-top:8px" href="hotels.php">Reset</a>
    </form>
    <div>
      <?php if (!$rows) : ?>
        <div class="card empty"><h3>No hotels match your search</h3><p>Try different dates, fewer filters or another city.</p></div>
      <?php else : ?>
        <div class="grid grid-3"><?php foreach ($rows as $h) { hotel_card($h, $qs); } ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php client_footer();
