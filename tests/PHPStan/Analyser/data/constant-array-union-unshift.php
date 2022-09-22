<?php

namespace ConstantArrayUnionUnshift;

use function PHPStan\Testing\assertType;

function () {
	if (random_int(0, 1)) {
		$array = ['a' => 1];
	} else {
		$array = ['b' => 1];
	}

	assertType('array{a: 1}|array{b: 1}', $array);

	array_unshift($array, 2);

	assertType('array{0: 2, a: 1}|array{0: 2, b: 1}', $array);
};
