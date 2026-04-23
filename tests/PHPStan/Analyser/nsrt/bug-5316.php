<?php

namespace Bug5316;

use function PHPStan\Testing\assertType;

function (): void {
	$map = [
		1 => 'foo',
		2 => 'foo',
		3 => 'bar',
	];
	$names = ['foo', 'bar', 'baz'];
	$array = ['foo' => [], 'bar' => [], 'baz' => []];

	foreach ($map as $value => $name) {
		$array[$name][] = $value;
	}


	foreach ($array as $name => $elements) {
		assertType('bool', count($elements) > 0);
		assertType('array{}|array{1, 2}|array{3}', $elements);
	}
};

/**
 * @param array<1|2|3, 'foo'|'bar'|'baz'> $map
 */
function (array $map): void {
	$array = ['foo' => [], 'bar' => [], 'baz' => []];

	foreach ($map as $value => $name) {
		$array[$name][] = $value;
	}


	foreach ($array as $name => $elements) {
		assertType('bool', count($elements) > 0);
		assertType('list<(int|string)>', $elements);
	}
};
