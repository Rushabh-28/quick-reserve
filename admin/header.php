<?php
/** Admin layout (top). Pages must have called require_admin() first and set $me / $page_title. */
$page_title = $page_title ?? 'Quick Reserve';
$current    = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php'            => ['Dashboard', 'mdi-home'],
    'user_select.php'      => ['Users', 'mdi-account-multiple'],
    'hotel_select.php'     => ['Hotels', 'mdi-domain'],
    'room_type_select.php' => ['Room types', 'mdi-tag-multiple'],
    'room_select.php'      => ['Rooms', 'mdi-hotel'],
    'booking_select.php'   => ['Bookings', 'mdi-calendar-check'],
    'payment_select.php'   => ['Payments', 'mdi-cash-multiple'],
    'coupon_select.php'    => ['Coupons', 'mdi-ticket-percent'],
    'review_select.php'    => ['Reviews', 'mdi-star'],
    'Reports/booking_report.php' => ['Booking report (PDF)', 'mdi-file-pdf'],
    '../Client/index.php'  => ['View guest site', 'mdi-open-in-new'],
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo h($page_title); ?> &middot; Quick Reserve</title>
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
          <a class="navbar-brand brand-logo" href="index.php"><img src="assets/images/qr-logo.svg" alt="Quick Reserve" style="height:44px;width:auto"></a>
          <a class="navbar-brand brand-logo-mini" href="index.php"><img src="assets/images/qr-logo-mini.svg" alt="QR" style="height:36px;width:auto"></a>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-stretch">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu"></span>
          </button>
          <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
              <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="nav-profile-img">
                  <img src="assets/images/faces/face1.jpg" alt="">
                  <span class="availability-status online"></span>
                </div>
                <div class="nav-profile-text">
                  <p class="mb-1 text-black"><?php echo h($me['User_name']); ?></p>
                </div>
              </a>
              <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                <a class="dropdown-item" href="edit_profile.php">
                  <i class="mdi mdi-cached me-2 text-success"></i> Edit Profile </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php">
                  <i class="mdi mdi-logout me-2 text-primary"></i> Sign out </a>
              </div>
            </li>
            <li class="nav-item d-none d-lg-block full-screen-link">
              <a class="nav-link">
                <i class="mdi mdi-fullscreen" id="fullscreen-button"></i>
              </a>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
          </button>
        </div>
      </nav>
      <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item nav-profile">
              <a href="edit_profile.php" class="nav-link">
                <div class="nav-profile-image">
                  <img src="assets/images/faces/face1.jpg" alt="profile">
                  <span class="login-status online"></span>
                </div>
                <div class="nav-profile-text d-flex flex-column">
                  <span class="font-weight-bold mb-2"><?php echo h($me['User_name']); ?></span>
                </div>
                <i class="mdi mdi-bookmark-check text-success nav-profile-badge"></i>
              </a>
            </li>
            <?php foreach ($nav as $file => [$label, $icon]) : ?>
            <li class="nav-item<?php echo $current === basename($file) ? ' active' : ''; ?>">
              <a class="nav-link" href="<?php echo h($file); ?>">
                <span class="menu-title"><?php echo h($label); ?></span>
                <i class="mdi <?php echo h($icon); ?> menu-icon"></i>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </nav>
        <div class="main-panel">
          <?php if (!empty($_SESSION['flash'])) : ?>
          <div style="padding:1.5rem 2rem 0"><?php render_flash(); ?></div>
          <?php endif; ?>
