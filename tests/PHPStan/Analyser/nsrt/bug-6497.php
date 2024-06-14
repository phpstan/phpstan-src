<?php

declare(strict_types=1);

namespace Bug6497;

use function PHPStan\Testing\assertType;

function bug6497(): void {
	/** @var array<int, array{foo: string, bar: int}> */
	$array = [
		['foo' => 'baz', 'bar' => 3],
	];

	$array2 = array_column($array, null, 'foo');

	assertType('array<string, array{foo: string, bar: int}>', $array2);
}
