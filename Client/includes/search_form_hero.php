<form class="search" method="get" action="hotels.php" data-dates>
  <div><label for="city">Where to?</label><select id="city" name="city"><option value="">All cities</option><?php foreach ($cities as $c) : ?><option value="<?php echo h($c); ?>"<?php echo $c === ($_GET["city"] ?? "") ? " selected" : ""; ?>><?php echo h($c); ?></option><?php endforeach; ?></select></div>
  <div><label for="in">Check-in</label><input id="in" type="date" name="in" value="<?php echo h($in); ?>" required></div>
  <div><label for="out">Check-out</label><input id="out" type="date" name="out" value="<?php echo h($out); ?>" required></div>
  <div><label for="guests">Guests</label><select id="guests" name="guests"><?php for ($i = 1; $i <= 6; $i++) : ?><option value="<?php echo $i; ?>"<?php echo $i === $guests ? " selected" : ""; ?>><?php echo $i; ?></option><?php endfor; ?></select></div>
  <button class="btn btn-amber" type="submit">Search</button>
</form>