<?php
/** Pricing: GST slabs, coupons, quotes. All money is INR rounded to 2 decimals. */

/** GST on hotel rooms depends on the declared tariff per night (India). */
function gst_rate(float $pricePerNight): float
{
    if ($pricePerNight <= 1000) { return 0.0; }
    if ($pricePerNight <= 7500) { return 12.0; }
    return 18.0;
}

function money(float|string|int $n): string
{
    return number_format((float) $n, 2);
}

/**
 * Look a coupon up and check that it can be used today.
 * Returns [couponRow|null, errorMessage]. Pass $subtotal to also check the minimum amount.
 */
function coupon_find(string $code, ?float $subtotal = null): array
{
    $code = strtoupper(trim($code));
    if ($code === '') { return [null, '']; }
    $c = db_one('SELECT * FROM coupon WHERE Code = ?', 's', [$code]);
    if (!$c || !$c['Active'])                                      { return [null, 'This coupon code is not valid.']; }
    $today = date('Y-m-d');
    if ($c['Valid_from'] && $today < $c['Valid_from'])             { return [null, 'This coupon is not active yet.']; }
    if ($c['Valid_to'] && $today > $c['Valid_to'])                 { return [null, 'This coupon has expired.']; }
    if ($c['Usage_limit'] !== null && $c['Used_count'] >= $c['Usage_limit']) { return [null, 'This coupon has reached its usage limit.']; }
    if ($subtotal !== null && $subtotal < (float) $c['Min_amount']) {
        return [null, 'Add ' . money($c['Min_amount']) . ' or more of room charges to use this coupon.'];
    }
    return [$c, ''];
}

/** @return array{nights:int,price:float,subtotal:float,discount:float,taxable:float,gst_rate:float,tax:float,total:float} */
function price_quote(float $pricePerNight, int $nights, ?array $coupon = null): array
{
    $subtotal = round($pricePerNight * $nights, 2);
    $discount = 0.0;
    if ($coupon && $subtotal >= (float) $coupon['Min_amount']) {
        $discount = $coupon['Type'] === 'percent' ? $subtotal * (float) $coupon['Value'] / 100 : (float) $coupon['Value'];
        if ($coupon['Max_discount'] !== null) { $discount = min($discount, (float) $coupon['Max_discount']); }
        $discount = round(min($discount, $subtotal), 2);
    }
    $taxable = round($subtotal - $discount, 2);
    $rate    = gst_rate($pricePerNight);
    $tax     = round($taxable * $rate / 100, 2);
    return ['nights' => $nights, 'price' => $pricePerNight, 'subtotal' => $subtotal, 'discount' => $discount,
            'taxable' => $taxable, 'gst_rate' => $rate, 'tax' => $tax, 'total' => round($taxable + $tax, 2)];
}
