<?php
/** Booking helpers shared by the Admin panel and the Client site. */
require_once __DIR__ . '/pricing.php';

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function nights_between(string $in, string $out): int
{
    return (int) (new DateTime($in))->diff(new DateTime($out))->days;
}

function room_price_per_night(int $roomId): ?float
{
    $r = db_one('SELECT rt.Room_type_price_per_night AS p FROM room r JOIN room_type rt ON rt.Room_type_id = r.Room_type_id WHERE r.Room_id = ?', 'i', [$roomId]);
    return $r ? (float) $r['p'] : null;
}

/** SQL fragment: bookings that currently block a room (not cancelled, not an expired unpaid hold). */
function blocking_sql(string $alias = ''): string
{
    $a = $alias === '' ? '' : $alias . '.';
    return "{$a}Booking_status <> 'cancelled' AND NOT ({$a}Booking_status = 'pending' AND {$a}Payment_status = 'unpaid' AND {$a}Hold_expires IS NOT NULL AND {$a}Hold_expires < NOW())";
}

function room_is_free(int $roomId, string $in, string $out, int $excludeBookingId = 0): bool
{
    $row = db_one(
        'SELECT Booking_id FROM booking WHERE Room_id = ? AND Booking_id <> ? AND ' . blocking_sql() . '
            AND Check_in_date < ? AND Check_out_date > ? LIMIT 1',
        'iiss', [$roomId, $excludeBookingId, $out, $in]
    );
    return $row === null;
}

/** Number of free rooms per room type for a hotel and a date range. */
function free_rooms_by_type(int $hotelId, string $in, string $out): array
{
    return db_all(
        "SELECT rt.*, COUNT(r.Room_id) AS free_count, MIN(r.Room_id) AS sample_room, MAX(r.Room_image) AS room_image
           FROM room r
           JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
          WHERE r.Hotel_id = ? AND r.Room_availability = 'yes'
            AND NOT EXISTS (SELECT 1 FROM booking b WHERE b.Room_id = r.Room_id AND " . blocking_sql('b') . "
                            AND b.Check_in_date < ? AND b.Check_out_date > ?)
          GROUP BY rt.Room_type_id
          ORDER BY rt.Room_type_price_per_night",
        'iss', [$hotelId, $out, $in]
    );
}

function find_free_room(int $hotelId, int $typeId, string $in, string $out): ?int
{
    $rooms = db_all("SELECT Room_id FROM room WHERE Hotel_id = ? AND Room_type_id = ? AND Room_availability = 'yes' ORDER BY Room_no", 'ii', [$hotelId, $typeId]);
    foreach ($rooms as $r) {
        if (room_is_free((int) $r['Room_id'], $in, $out)) { return (int) $r['Room_id']; }
    }
    return null;
}

function new_booking_ref(): string
{
    return 'QR' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Create a booking safely (room row is locked so two guests cannot take the same room).
 * $a: user_id, room_id, in, out, guests, coupon_code, status, hold_minutes (0 = no hold)
 * Returns ['id' => int, 'ref' => string, 'quote' => array]. Throws RuntimeException with a user-friendly message.
 */
function create_booking(array $a): array
{
    global $conn;
    $in = $a['in']; $out = $a['out'];
    if (!valid_date($in) || !valid_date($out))   { throw new RuntimeException('Please choose valid dates.'); }
    if ($out <= $in)                              { throw new RuntimeException('Check-out must be after check-in.'); }
    if ($in < date('Y-m-d') && empty($a['allow_past'])) { throw new RuntimeException('Check-in cannot be in the past.'); }
    $nights = nights_between($in, $out);
    if ($nights > 30)                             { throw new RuntimeException('Stays are limited to 30 nights.'); }

    $conn->begin_transaction();
    try {
        $room = db_one("SELECT r.Room_id, rt.Room_type_capacity cap, rt.Room_type_price_per_night ppn
                          FROM room r JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
                         WHERE r.Room_id = ? AND r.Room_availability = 'yes' FOR UPDATE", 'i', [(int) $a['room_id']]);
        if (!$room)                                         { throw new RuntimeException('That room is not available.'); }
        if ((int) $a['guests'] < 1 || (int) $a['guests'] > (int) $room['cap']) {
            throw new RuntimeException('This room sleeps up to ' . (int) $room['cap'] . ' guests.');
        }
        if (!room_is_free((int) $a['room_id'], $in, $out))  { throw new RuntimeException('Sorry, that room was just booked for some of those dates.'); }

        $coupon = null;
        if (!empty($a['coupon_code'])) {
            $sub = round((float) $room['ppn'] * $nights, 2);
            [$coupon, $err] = coupon_find($a['coupon_code'], $sub);
            if (!$coupon) { throw new RuntimeException($err ?: 'Invalid coupon.'); }
        }
        $q = price_quote((float) $room['ppn'], $nights, $coupon);
        $hold = (int) ($a['hold_minutes'] ?? 0);
        $ref  = new_booking_ref();
        db_exec('INSERT INTO booking (Booking_ref, User_id, Room_id, Guests, Check_in_date, Check_out_date, Subtotal, Coupon_code, Discount, Tax, Total_price, Booking_status, Hold_expires)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?, ' . ($hold > 0 ? 'DATE_ADD(NOW(), INTERVAL ' . $hold . ' MINUTE)' : 'NULL') . ')',
            'siiissdsddds', [$ref, (int) $a['user_id'], (int) $a['room_id'], (int) $a['guests'], $in, $out, $q['subtotal'],
                             $coupon['Code'] ?? null, $q['discount'], $q['tax'], $q['total'], $a['status'] ?? 'pending']);
        $id = db_insert_id();
        $conn->commit();
        return ['id' => $id, 'ref' => $ref, 'quote' => $q];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e instanceof RuntimeException ? $e : new RuntimeException('Could not create the booking. Please try again.');
    }
}

/** Mark a booking paid: confirms it, clears the hold, assigns the invoice number (once). */
function mark_booking_paid(int $bookingId): void
{
    $b = db_one('SELECT * FROM booking WHERE Booking_id = ?', 'i', [$bookingId]);
    if (!$b) { return; }
    if ($b['Booking_status'] === 'cancelled') {
        // Payment arrived after the booking was cancelled / its hold expired: revive it if the room is still free.
        if (room_is_free((int) $b['Room_id'], $b['Check_in_date'], $b['Check_out_date'], $bookingId) && $b['Check_in_date'] >= date('Y-m-d')) {
            db_exec("UPDATE booking SET Booking_status = 'pending' WHERE Booking_id = ?", 'i', [$bookingId]);
        } else {
            db_exec("UPDATE booking SET Payment_status = 'refund_pending' WHERE Booking_id = ?", 'i', [$bookingId]);
            return;
        }
    }
    $invoice = $b['Invoice_no'] ?: 'INV-' . date('Y') . '-' . str_pad((string) $bookingId, 5, '0', STR_PAD_LEFT);
    db_exec("UPDATE booking SET Payment_status = 'paid', Hold_expires = NULL, Invoice_no = ?,
                    Booking_status = IF(Booking_status IN ('pending'), 'confirmed', Booking_status)
              WHERE Booking_id = ?", 'si', [$invoice, $bookingId]);
    if (!$b['Invoice_no'] && $b['Coupon_code']) {
        db_exec('UPDATE coupon SET Used_count = Used_count + 1 WHERE Code = ?', 's', [$b['Coupon_code']]);
    }
}

/** Recalculate Payment_status from recorded payments (used after admin edits payments). */
function sync_payment_status(int $bookingId): void
{
    $b = db_one('SELECT Total_price, Payment_status FROM booking WHERE Booking_id = ?', 'i', [$bookingId]);
    if (!$b) { return; }
    $paid = (float) db_one("SELECT COALESCE(SUM(Amount),0) s FROM payment WHERE Booking_id = ? AND Status = 'paid'", 'i', [$bookingId])['s'];
    if ($paid + 0.005 >= (float) $b['Total_price'] && $paid > 0) {
        mark_booking_paid($bookingId);
    } elseif ($b['Payment_status'] === 'paid') {
        db_exec("UPDATE booking SET Payment_status = 'unpaid' WHERE Booking_id = ?", 'i', [$bookingId]);
    }
}

function booking_can_cancel(array $b): bool
{
    return in_array($b['Booking_status'], ['pending', 'confirmed'], true) && $b['Check_in_date'] > date('Y-m-d');
}

/** Cancel; paid bookings go to refund_pending so staff can issue the refund. */
function cancel_booking(int $bookingId): void
{
    db_exec("UPDATE booking SET Booking_status = 'cancelled', Hold_expires = NULL,
                    Payment_status = IF(Payment_status = 'paid', 'refund_pending', Payment_status)
              WHERE Booking_id = ? AND Booking_status IN ('pending','confirmed')", 'i', [$bookingId]);
}

/** Unpaid online bookings whose hold ran out are cancelled (call on busy pages; cheap). */
function release_expired_holds(): void
{
    db_exec("UPDATE booking SET Booking_status = 'cancelled', Hold_expires = NULL
              WHERE Booking_status = 'pending' AND Payment_status = 'unpaid' AND Hold_expires IS NOT NULL AND Hold_expires < NOW()");
}

function hotel_rating_stats(int $hotelId): array
{
    $r = db_one('SELECT COUNT(*) n, COALESCE(AVG(Rating),0) avg FROM review WHERE Hotel_id = ?', 'i', [$hotelId]);
    return ['count' => (int) $r['n'], 'avg' => round((float) $r['avg'], 1)];
}
