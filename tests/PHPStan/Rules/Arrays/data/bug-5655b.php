<?php declare(strict_types=1);

namespace Bug5655b;

use WeakMap;
use stdClass;


function doFoo() {
	// Case with list... OK

	/** @var list<array{foo: string, bar?: string}> */
	$list = [];

	$list[] = [
		'foo' => 'baz',
	];

// Case with map... FAIL

	/** @var WeakMap<object, array{foo: string, bar?: string}> */
	$map = new WeakMap();

	$map[new stdClass()] = [
		'foo' => 'foo',
		'bar' => 'bar',
	];

	$map[new stdClass()] = [
		'foo' => 'baz',
	];
}

