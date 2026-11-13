<?php
function room_type_collect(): array
{
    $v = ['name' => post_str('room_type_name'), 'description' => post_str('room_type_description'),
          'capacity' => post_str('room_type_capacity'), 'price' => post_str('room_type_pricepernight')];
    $e = [];
    if ($v['name'] === '') { $e['room_type_name'] = 'Name is required'; }
    if ($v['description'] === '') { $e['room_type_description'] = 'Description is required'; }
    if (!ctype_digit($v['capacity']) || (int) $v['capacity'] < 1 || (int) $v['capacity'] > 20) { $e['room_type_capacity'] = 'Capacity must be a whole number from 1 to 20'; }
    if (!is_numeric($v['price']) || (float) $v['price'] < 0 || (float) $v['price'] > 99999999) { $e['room_type_pricepernight'] = 'Enter a valid price'; }
    return [$v, $e];
}
