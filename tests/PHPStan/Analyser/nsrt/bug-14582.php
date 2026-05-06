<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14582;

use function PHPStan\Testing\assertType;

function testArrayFilter(): void
{
	$b = array_filter([], fn() => throw new \Error());
	assertType('array{}', $b);
}

function testArrayMap(): void
{
	$result = array_map(fn() => throw new \Error(), []);
	assertType('array{}', $result);
}
