<?php

namespace InvalidKeyArrayItemMixed;

/** @var mixed $foo */
$foo = doFoo();
$bar = doFoo();

$a = [
	$foo => 'aaa',
	$bar => 'aaa',
];
