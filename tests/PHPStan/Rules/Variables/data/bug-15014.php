<?php declare(strict_types = 1);

namespace Bug15014;

$fields = [
	'field_1',
	'field_2',
];

foreach ($fields as $field) {
	$var = ${$field} ?? null;
}
