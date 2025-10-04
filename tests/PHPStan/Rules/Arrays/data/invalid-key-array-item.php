<?php

namespace InvalidKeyArrayItem;

/** @var string|\stdClass $stringOrObject */
$stringOrObject = doFoo();

$a = [
	'foo',
	1 => 'aaa',
	'1' => 'aaa',
	new \DateTimeImmutable() => 'aaa',
	[] => 'bbb',
	$stringOrObject => 'aaa',
];

/** @var mixed $mixed */
$mixed = doFoo();

$b = [
	$mixed => 'foo',
];

// PHP version dependent
$c = [
	1.0 => 'aaa',
	null => 'aaa',
];
