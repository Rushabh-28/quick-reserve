<?php
require_once __DIR__ . '/includes/client.php';
unset($_SESSION['guest_id']);
session_regenerate_id(true);
flash('info', 'You have been signed out.');
redirect('index.php');
