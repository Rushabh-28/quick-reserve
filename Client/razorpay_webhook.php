<?php
/**
 * Razorpay webhook (Dashboard > Settings > Webhooks). Subscribe to: payment.captured, order.paid.
 * URL: https://your-domain/Client/razorpay_webhook.php   Secret: razorpay_webhook_secret in config.
 * It confirms bookings even if the guest closes the browser before returning to the site.
 */
require_once __DIR__ . '/includes/client.php';

$secret = $qr_config['razorpay_webhook_secret'];
if ($secret === '') { http_response_code(503); exit('Webhook secret not configured.'); }

$body = file_get_contents('php://input') ?: '';
$sig  = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
if (!hash_equals(hash_hmac('sha256', $body, $secret), $sig)) { http_response_code(400); exit('Bad signature.'); }

$event = json_decode($body, true);
$type = $event['event'] ?? '';
if (in_array($type, ['payment.captured', 'order.paid'], true)) {
    $p = $event['payload']['payment']['entity'] ?? [];
    if (!empty($p['order_id']) && !empty($p['id'])) {
        razorpay_record_success((string) $p['order_id'], (string) $p['id'], 'webhook');
    }
}
http_response_code(200);
echo 'ok';
