<?php
/**
 * Razorpay integration (Orders API + Checkout.js signature verification).
 * With no API keys configured it runs a DEMO gateway so the full flow works offline.
 */

function razorpay_live(): bool
{
    global $qr_config;
    return $qr_config['razorpay_key_id'] !== '' && $qr_config['razorpay_key_secret'] !== '';
}

function razorpay_secret(): string
{
    global $qr_config;
    return razorpay_live() ? $qr_config['razorpay_key_secret'] : 'quick-reserve-demo-secret';
}

/** Create a gateway order for $amount rupees. Returns ['id' => 'order_xxx', 'amount' => paise]. */
function razorpay_create_order(float $amount, string $receipt): array
{
    global $qr_config;
    $paise = (int) round($amount * 100);
    if (!razorpay_live()) {
        return ['id' => 'order_DEMO' . strtoupper(bin2hex(random_bytes(6))), 'amount' => $paise, 'demo' => true];
    }
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => $qr_config['razorpay_key_id'] . ':' . $qr_config['razorpay_key_secret'],
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['amount' => $paise, 'currency' => 'INR', 'receipt' => $receipt, 'payment_capture' => 1]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $data = is_string($body) ? json_decode($body, true) : null;
    if ($code !== 200 || empty($data['id'])) {
        error_log('Razorpay order failed: HTTP ' . $code . ' ' . $err . ' ' . (is_string($body) ? $body : ''));
        throw new RuntimeException('The payment gateway is unavailable right now. Please try again in a moment.');
    }
    return ['id' => $data['id'], 'amount' => (int) $data['amount'], 'demo' => false];
}

/** Checkout.js signature: HMAC-SHA256("order_id|payment_id", key_secret). */
function razorpay_sign(string $orderId, string $paymentId): string
{
    return hash_hmac('sha256', $orderId . '|' . $paymentId, razorpay_secret());
}

function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool
{
    if ($orderId === '' || $paymentId === '' || $signature === '') { return false; }
    return hash_equals(razorpay_sign($orderId, $paymentId), $signature);
}

/**
 * Record a successful payment for the booking that owns $orderId (idempotent).
 * Returns the booking id, or null if the order is unknown.
 */
function razorpay_record_success(string $orderId, string $paymentId, string $signature, string $method = 'razorpay'): ?int
{
    $p = db_one('SELECT Payment_id, Booking_id, Status FROM payment WHERE Gateway_order_id = ?', 's', [$orderId]);
    if (!$p) { return null; }
    if ($p['Status'] !== 'paid') {
        db_exec("UPDATE payment SET Status = 'paid', Gateway_payment_id = ?, Gateway_signature = ?, Payment_method = ?, Payment_date = NOW() WHERE Payment_id = ?",
            'sssi', [$paymentId, $signature, $method, (int) $p['Payment_id']]);
        mark_booking_paid((int) $p['Booking_id']);
    }
    return (int) $p['Booking_id'];
}
