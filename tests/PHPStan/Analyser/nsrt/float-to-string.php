<?php

namespace FloatToString;

use function PHPStan\Testing\assertType;

/**
 * @param int|float $number
 */
function doFoo(float $float, $number): void
{
	// a finite float stringifies as a numeric string, INF, -INF and NAN as 'INF', '-INF' and 'NAN'
	assertType("'-INF'|'INF'|'NAN'|(numeric-string&uppercase-string)", (string) $float);
	assertType("'-INF'|'INF'|'NAN'|(numeric-string&uppercase-string)", '' . $float);
	assertType('non-falsy-string&uppercase-string', $float . '1');
	assertType("'-INF'|'INF'|'NAN'|(numeric-string&uppercase-string)", (string) $number);

	// constant floats stay exact
	assertType("'1.5'", (string) 1.5);
	assertType("'INF'", (string) INF);
}

/**
 * @param list<float|null> $prices
 */
function arrayKey(array $prices): void
{
	// (string) 10.0 is '10', which PHP turns into an int key
	$map = [];
	foreach ($prices as $i => $price) {
		$map[(string) ($price ?? 0.0)][] = $i;
	}
	assertType("array<'-INF'|'INF'|'NAN'|int|(numeric-string&uppercase-string), non-empty-list<int<0, max>>>", $map);
}
