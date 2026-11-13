<?php
function room_options(): array
{
    $hotels = array_column(db_all('SELECT Hotel_id, Hotel_name FROM hotel ORDER BY Hotel_name'), 'Hotel_name', 'Hotel_id');
    $types  = array_column(db_all('SELECT Room_type_id, Room_type_name FROM room_type ORDER BY Room_type_name'), 'Room_type_name', 'Room_type_id');
    return [$hotels, $types];
}

function room_collect(array $hotels, array $types, int $editId = 0): array
{
    $v = ['hotel' => post_str('hotel_name'), 'type' => post_str('room_type'), 'no' => post_str('room_no'), 'availability' => post_str('room_availability')];
    $e = [];
    if (!isset($hotels[(int) $v['hotel']])) { $e['hotel_name'] = 'Select a hotel'; }
    if (!isset($types[(int) $v['type']]))   { $e['room_type'] = 'Select a room type'; }
    if ($v['no'] === '' || strlen($v['no']) > 20) { $e['room_no'] = 'Room no is required (max 20 characters)'; }
    if (!in_array($v['availability'], ['yes', 'no'], true)) { $e['room_availability'] = 'Choose availability'; }
    if (!$e) {
        $dup = db_one('SELECT Room_id FROM room WHERE Hotel_id = ? AND Room_no = ? AND Room_id <> ?', 'isi', [(int) $v['hotel'], $v['no'], $editId]);
        if ($dup) { $e['room_no'] = 'This hotel already has a room with that number'; }
    }
    return [$v, $e];
}
