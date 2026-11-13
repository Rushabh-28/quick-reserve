<?php
function coupon_collect(int $editId = 0): array
{
    $v = ['code' => strtoupper(post_str('code')), 'description' => post_str('description'), 'type' => post_str('type'),
          'value' => post_str('value'), 'min_amount' => post_str('min_amount') ?: '0', 'max_discount' => post_str('max_discount'),
          'valid_from' => post_str('valid_from'), 'valid_to' => post_str('valid_to'), 'usage_limit' => post_str('usage_limit'),
          'active' => post_str('active')];
    $e = [];
    if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $v['code'])) { $e['code'] = 'Use 3-30 letters, numbers, - or _'; }
    elseif (db_one('SELECT Coupon_id FROM coupon WHERE Code = ? AND Coupon_id <> ?', 'si', [$v['code'], $editId])) { $e['code'] = 'This code already exists'; }
    if (!in_array($v['type'], ['percent', 'flat'], true)) { $e['type'] = 'Choose a type'; }
    if (!is_numeric($v['value']) || (float) $v['value'] <= 0) { $e['value'] = 'Enter a value above 0'; }
    elseif ($v['type'] === 'percent' && (float) $v['value'] > 100) { $e['value'] = 'A percentage cannot exceed 100'; }
    if (!is_numeric($v['min_amount']) || (float) $v['min_amount'] < 0) { $e['min_amount'] = 'Enter 0 or more'; }
    if ($v['max_discount'] !== '' && (!is_numeric($v['max_discount']) || (float) $v['max_discount'] <= 0)) { $e['max_discount'] = 'Enter an amount above 0'; }
    foreach (['valid_from', 'valid_to'] as $k) {
        if ($v[$k] !== '' && !valid_date($v[$k])) { $e[$k] = 'Invalid date'; }
    }
    if (!isset($e['valid_from'], $e['valid_to']) && $v['valid_from'] !== '' && $v['valid_to'] !== '' && $v['valid_to'] < $v['valid_from']) { $e['valid_to'] = 'Must be after the start date'; }
    if ($v['usage_limit'] !== '' && (!ctype_digit($v['usage_limit']) || (int) $v['usage_limit'] < 1)) { $e['usage_limit'] = 'Enter a whole number'; }
    if (!in_array($v['active'], ['0', '1'], true)) { $e['active'] = 'Choose Yes or No'; }
    return [$v, $e];
}

function coupon_params(array $v): array
{
    return [$v['code'], $v['description'], $v['type'], (float) $v['value'], (float) $v['min_amount'],
            $v['max_discount'] === '' ? null : (float) $v['max_discount'], $v['valid_from'] ?: null, $v['valid_to'] ?: null,
            $v['usage_limit'] === '' ? null : (int) $v['usage_limit'], (int) $v['active']];
}
