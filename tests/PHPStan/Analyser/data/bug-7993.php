<?php declare(strict_types = 1);

namespace Bug7993;

use function PHPStan\Testing\assertType;

function () {
	assertType('array{1: 2, 3: 4}', array_filter(range(1, 5), static fn(int $value): bool => 0 === $value % 2));
};
