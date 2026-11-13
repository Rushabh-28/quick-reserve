<?php
/** Shared code for the guest-facing site. */
declare(strict_types=1);

require_once __DIR__ . '/../../Admin/includes/bootstrap.php';
require_once __DIR__ . '/../../Admin/includes/booking_lib.php';
require_once __DIR__ . '/../../Admin/includes/razorpay_lib.php';

const ASSET_ADMIN = '../Admin/';

/* ---------- guest authentication (any row in `user` can book) ---------- */

function guest(): ?array
{
    static $g = false;
    if ($g === false) {
        $g = null;
        if (!empty($_SESSION['guest_id'])) {
            $g = db_one('SELECT * FROM `user` WHERE User_id = ?', 'i', [(int) $_SESSION['guest_id']]);
            if (!$g) { unset($_SESSION['guest_id']); }
        }
    }
    return $g;
}

function require_guest(): array
{
    $g = guest();
    if (!$g) {
        flash('info', 'Please sign in to continue.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
    }
    return $g;
}

/** Only allow redirects to pages inside the Client folder. */
function safe_next(?string $next): string
{
    if ($next && preg_match('#^[A-Za-z0-9_\-./?=&%:+,]*$#', $next) && !str_contains($next, '//') && !str_starts_with($next, '/') && !str_contains($next, '..')) {
        return $next;
    }
    return 'index.php';
}

function guest_login(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['guest_id'] = $id;
}

/* ---------- misc helpers ---------- */

function inr($n): string
{
    return '₹' . number_format((float) $n, 0, '.', ',');
}

function inr2($n): string
{
    return '₹' . number_format((float) $n, 2, '.', ',');
}

function hotel_img(?string $name): string
{
    return is_upload_name($name) ? ASSET_ADMIN . 'uploads/' . $name : 'assets/placeholder.svg';
}

function stars(float $n): string
{
    $full = (int) round($n);
    return str_repeat('★', $full) . str_repeat('☆', max(0, 5 - $full));
}

function amenity_list(string $csv): array
{
    return array_values(array_filter(array_map('trim', explode(',', $csv))));
}

/** Read and validate the search dates from the query string (defaults: tomorrow +2 nights). */
function search_dates(): array
{
    $in  = $_GET['in']  ?? '';
    $out = $_GET['out'] ?? '';
    $has = valid_date((string) $in) && valid_date((string) $out) && $out > $in && $in >= date('Y-m-d');
    if (!$has) {
        $in  = date('Y-m-d', strtotime('+1 day'));
        $out = date('Y-m-d', strtotime('+3 days'));
    }
    return [$in, $out, $has];
}

function badge(string $text, string $kind = 'gray'): string
{
    return '<span class="badge badge-' . h($kind) . '">' . h($text) . '</span>';
}

/* ---------- layout ---------- */

function client_header(string $title, string $bodyClass = ''): void
{
    $g = guest();
    $cur = basename($_SERVER['PHP_SELF']);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($title); ?> · Quick Reserve</title>
  <meta name="description" content="Book hotels across India with instant confirmation and a GST invoice.">
  <link rel="icon" type="image/png" href="../Admin/assets/images/favicon.png">
  <link rel="stylesheet" href="assets/client.css">
</head>
<body class="<?php echo h($bodyClass); ?>">
<header class="site-header">
  <div class="wrap nav">
    <a class="brand" href="index.php"><img src="../Admin/assets/images/qr-logo.svg" alt="Quick Reserve"></a>
    <button class="nav-toggle" type="button" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
    <nav class="nav-links">
      <a href="hotels.php" class="<?php echo $cur === 'hotels.php' ? 'active' : ''; ?>">Hotels</a>
      <a href="my_bookings.php" class="<?php echo $cur === 'my_bookings.php' ? 'active' : ''; ?>">My bookings</a>
      <?php if ($g) : ?>
        <a href="profile.php" class="<?php echo $cur === 'profile.php' ? 'active' : ''; ?>">Hi, <?php echo h(explode(' ', $g['User_name'])[0]); ?></a>
        <a href="logout.php" class="btn btn-ghost btn-sm">Sign out</a>
      <?php else : ?>
        <a href="login.php" class="btn btn-ghost btn-sm">Sign in</a>
        <a href="register.php" class="btn btn-primary btn-sm">Create account</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main>
<?php if (!empty($_SESSION['flash'])) : ?><div class="wrap flash-area"><?php client_flash(); ?></div><?php endif; ?>
<?php
}

function client_flash(): void
{
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = $f['type'] === 'error' ? 'danger' : $f['type'];
        echo '<div class="alert alert-' . h($cls) . '" role="alert">' . h($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function client_footer(): void
{ ?>
</main>
<footer class="site-footer">
  <div class="wrap foot">
    <div><img src="../Admin/assets/images/qr-logo.svg" alt="Quick Reserve" style="height:38px"><p>Hand-picked hotels, instant confirmation and a GST invoice for every stay.</p></div>
    <div><h4>Explore</h4><a href="hotels.php">All hotels</a><a href="my_bookings.php">My bookings</a><a href="../Admin/login.php">Staff login</a></div>
    <div><h4>Payments</h4><p>Secure checkout by Razorpay: UPI, cards, net banking and wallets.</p></div>
  </div>
  <div class="wrap copy">© <?php echo date('Y'); ?> Quick Reserve. Demo project: all hotels and guests are fictional.</div>
</footer>
<script src="assets/client.js"></script>
</body>
</html>
<?php }

/** Reusable hotel card. $h needs: Hotel_id, Hotel_name, Hotel_city, Hotel_image, Hotel_rating, minp, avg_rating, review_count */
function hotel_card(array $h, string $qs = ''): void
{
    $avg = (float) $h['avg_rating']; ?>
  <a class="card hotel-card" href="hotel.php?id=<?php echo (int) $h['Hotel_id'] . $qs; ?>">
    <div class="thumb"><img loading="lazy" src="<?php echo h(hotel_img($h['Hotel_image'])); ?>" alt="<?php echo h($h['Hotel_name']); ?>">
      <span class="chip"><?php echo (int) $h['Hotel_rating']; ?>★ hotel</span></div>
    <div class="card-body">
      <h3><?php echo h($h['Hotel_name']); ?></h3>
      <p class="muted">📍 <?php echo h($h['Hotel_city']); ?></p>
      <p class="rating"><?php if ((int) $h['review_count'] > 0) : ?><strong><?php echo number_format($avg, 1); ?></strong> <span class="stars"><?php echo stars($avg); ?></span> <small>(<?php echo (int) $h['review_count']; ?>)</small><?php else : ?><small class="muted">New on Quick Reserve</small><?php endif; ?></p>
      <p class="price">from <strong><?php echo inr($h['minp']); ?></strong> <small>/ night</small></p>
    </div>
  </a>
<?php }
