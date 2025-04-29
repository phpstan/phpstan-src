<?php declare(strict_types = 1);

namespace Bug10893;

use function PHPStan\Testing\assertType;

/**
 * @param non-falsy-string&numeric-string $str
 */
function hasMicroseconds(\DateTimeInterface $value, string $str): bool
{
	assertType('non-falsy-string&numeric-string', $str);
	assertType('int<min, -1>|int<1, max>', (int)$str);
	assertType('true', (int)$str !== 0);

	assertType('non-falsy-string&numeric-string', $value->format('u'));
	assertType('int<min, -1>|int<1, max>', (int)$value->format('u'));
	assertType('true', (int)$value->format('u') !== 0);

	assertType('non-falsy-string&numeric-string', $value->format('v'));
	assertType('int<min, -1>|int<1, max>', (int)$value->format('v'));
	assertType('true', (int)$value->format('v') !== 0);

	assertType('float', $value->format('u') * 1e-6);
	assertType('float', $value->format('v') * 1e-3);

	return (int) $value->format('u') !== 0;
}
