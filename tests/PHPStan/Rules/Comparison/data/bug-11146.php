<?php declare(strict_types = 1);

namespace Bug11146;

/** @var array<string, string> $array1 */
$array1 = [];
/** @var array<string, string> $array2 */
$array2 = [];

$array1['test'] = 'test';
$array2['test'] = 'test';
foreach (['not-test', 'test'] as $key) {
	if (isset($array1[$key])) {
		unset($array1[$key]);
	}
}
unset($array2['test']);

if (count($array1) > 0) {
	echo 'hi';
}
