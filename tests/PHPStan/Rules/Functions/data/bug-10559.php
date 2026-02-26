<?php

namespace Bug10559;

/** @var mixed[] $arr1 */
$arr1 = [
	'a1' => 1,
	'b1' => '2',
	'c1' => 3.3,
];
// There is no error
echo (string)$arr1['c1'];


/** @var mixed[] $arr2 */
$arr2 = [
	'a1' => 1,
	'b1' => '2',
	'c1' => 3.3,
];
if ($arr2['a1'] > 1) {}
echo (string)$arr2['c1'];
