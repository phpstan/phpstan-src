<?php declare(strict_types = 1);

namespace Bug8592;

use function PHPStan\Testing\assertType;

/**
 * @param array<numeric-string, mixed> $foo
 */
function foo(array $foo): void
{
	foreach ($foo as $key => $value) {
		assertType('int|numeric-string', $key);
	}
}
