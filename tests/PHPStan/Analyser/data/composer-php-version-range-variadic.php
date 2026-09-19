<?php declare(strict_types = 1);

namespace ComposerPhpVersionRangeVariadic;

use function PHPStan\Testing\assertType;

function doFoo(int ...$args): void
{
	assertType('list<int>', $args);
	assertType('int<70400, 70499>', PHP_VERSION_ID);
}
