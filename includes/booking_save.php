<?php
require_once __DIR__ . '/booking_lib.php';

function booking_options(): array
{
    $guests = array_column(db_all('SELECT User_id, CONCAT(User_name, " (", User_email, ")") AS label FROM `user` ORDER BY User_name'), 'label', 'User_id');
    $rooms  = array_column(db_all(
        'SELECT r.Room_id, CONCAT(h.Hotel_name, " - Room ", r.Room_no, " (", rt.Room_type_name, ", ", FORMAT(rt.Room_type_price_per_night, 2), "/night)") AS label
           FROM room r JOIN hotel h ON h.Hotel_id = r.Hotel_id JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
          ORDER BY h.Hotel_name, r.Room_no'), 'label', 'Room_id');
    return [$guests, $rooms];
}

/** Validate the booking form. Returns [values, errors]. Money is calculated by the caller. */
function booking_collect(array $guests, array $rooms, int $editId = 0): array
{
    $v = ['user' => post_str('user_id'), 'room' => post_str('room_id'), 'in' => post_str('check_in'),
          'out' => post_str('check_out'), 'guests' => post_str('guests'), 'coupon' => strtoupper(post_str('coupon')),
          'status' => post_str('status')];
    $e = [];
    if (!isset($guests[(int) $v['user']])) { $e['user_id'] = 'Select a guest'; }
    if (!isset($rooms[(int) $v['room']]))  { $e['room_id'] = 'Select a room'; }
    if (!valid_date($v['in']))  { $e['check_in'] = 'Enter a valid check-in date'; }
    if (!valid_date($v['out'])) { $e['check_out'] = 'Enter a valid check-out date'; }
    if (!ctype_digit($v['guests']) || (int) $v['guests'] < 1) { $e['guests'] = 'Enter the number of guests'; }
    if (!in_array($v['status'], ['pending', 'confirmed', 'cancelled', 'completed'], true)) { $e['status'] = 'Choose a status'; }
    if (!$e && $v['out'] <= $v['in']) { $e['check_out'] = 'Check-out must be after check-in'; }
    if (!$e) {
        $cap = db_one('SELECT rt.Room_type_capacity c FROM room r JOIN room_type rt ON rt.Room_type_id = r.Room_type_id WHERE r.Room_id = ?', 'i', [(int) $v['room']]);
        if ($cap && (int) $v['guests'] > (int) $cap['c']) { $e['guests'] = 'This room sleeps up to ' . (int) $cap['c'] . ' guests'; }
    }
    if (!$e && $v['status'] !== 'cancelled' && !room_is_free((int) $v['room'], $v['in'], $v['out'], $editId)) {
        $e['room_id'] = 'That room is already booked for some of those dates';
    }
    return [$v, $e];
}
