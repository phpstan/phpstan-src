<?php declare(strict_types = 1);

namespace Bug6704;

use DateTimeImmutable;
use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @param class-string<DateTimeImmutable>|class-string<stdClass> $a
 * @param DateTimeImmutable|stdClass $b
 */
function foo(string $a, object $b): void
{
	if (!is_a($a, stdClass::class, true)) {
		assertType('class-string<DateTimeImmutable>', $a);
	}

	if (!is_a($b, stdClass::class)) {
		assertType('DateTimeImmutable', $b);
	}
}
