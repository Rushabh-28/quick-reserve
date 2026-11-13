<?php
/** PDF invoice. Allowed for the guest who owns the booking, or a signed-in admin. */
require_once __DIR__ . '/includes/client.php';
require_once __DIR__ . '/../Admin/includes/invoice_lib.php';

$b = invoice_data((string) ($_GET['ref'] ?? ''));
$g = guest();
$allowed = $b && (($g && (int) $g['User_id'] === (int) $b['User_id']) || current_user());
if (!$allowed) {
    if (!$g && !current_user()) { require_guest(); }
    http_response_code(404); exit('Invoice not found.');
}
if (!$b['Invoice_no']) { http_response_code(409); exit('An invoice is issued once the booking is paid.'); }
output_invoice($b, isset($_GET['download']));
