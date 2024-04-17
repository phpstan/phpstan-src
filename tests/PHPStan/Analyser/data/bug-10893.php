<?php declare(strict_types = 1);

namespace Bug10893;

use function PHPStan\Testing\assertType;

/**
 * @param non-falsy-string $nonfalsy
 */
function hasMicroseconds(\DateTimeInterface $value, string $nonfalsy): bool
{
	assertType('non-falsy-string', $value->format('u'));
	assertType('int', (int)$value->format('u'));
	assertType('bool', (int)$value->format('u') !== 0);
	assertType('non-falsy-string', $nonfalsy);
	assertType('int', (int)$nonfalsy);
	assertType('bool', (int)$nonfalsy !== 0);

	return (int) $value->format('u') !== 0;
}
