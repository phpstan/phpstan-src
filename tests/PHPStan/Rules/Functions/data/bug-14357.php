<?php declare(strict_types = 1);

namespace Bug14357;

$data = [3, 1, 2];
$data2 = ['c', 'a', 'b'];

// Valid usages - no errors expected
array_multisort($data);
array_multisort($data, SORT_ASC);
array_multisort($data, SORT_DESC);
array_multisort($data, SORT_ASC, SORT_NUMERIC);
array_multisort($data, SORT_DESC, SORT_STRING);
array_multisort($data, SORT_ASC, SORT_REGULAR, $data2);
array_multisort($data, SORT_ASC, SORT_NATURAL);
array_multisort($data, SORT_ASC, SORT_LOCALE_STRING);
array_multisort($data, SORT_ASC, SORT_FLAG_CASE);
array_multisort($data, SORT_ASC, $data2, SORT_DESC);

// Invalid usage - should report error
array_multisort($data, 999);
