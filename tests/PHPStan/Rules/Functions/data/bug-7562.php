<?php declare(strict_types=1);

namespace Bug7562;

use function PHPStan\Testing\assertType;

/**
 * @param ''|class-string $p
 */
function ok($p): void
{
}

/**
 * @param ''|class-string<T> $p
 * @template T of object
 */
function ng($p): void
{
}

function doFoo() {
	ok('');
	ng('');
}
