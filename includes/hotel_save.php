<?php
function hotel_collect(): array
{
    $v = ['name' => post_str('hotel_name'), 'email' => post_str('hotel_email'), 'address' => post_str('hotel_address'),
          'phone' => post_str('hotel_phoneno'), 'city' => post_str('hotel_city'), 'amenities' => post_str('hotel_amenities'), 'rating' => post_str('rating'), 'description' => post_str('hotel_description')];
    $e = [];
    if ($v['name'] === '')    { $e['hotel_name'] = 'Hotel name is required'; }
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) { $e['hotel_email'] = 'A valid email is required'; }
    if ($v['city'] === '' || strlen($v['city']) > 80) { $e['hotel_city'] = 'City is required'; }
    if (strlen($v['amenities']) > 500) { $e['hotel_amenities'] = 'Keep amenities under 500 characters'; }
    if ($v['address'] === '') { $e['hotel_address'] = 'Address is required'; }
    if (!valid_phone($v['phone'])) { $e['hotel_phoneno'] = 'Enter a valid phone number'; }
    if (!in_array($v['rating'], ['1', '2', '3', '4', '5'], true)) { $e['rating'] = 'Choose a rating from 1 to 5'; }
    if ($v['description'] === '') { $e['hotel_description'] = 'Description is required'; }
    return [$v, $e];
}
