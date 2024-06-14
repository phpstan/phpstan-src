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
		assertType('list<1|2|3>', $elements);
	}
};
