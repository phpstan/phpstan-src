<?php declare(strict_types = 1);

namespace Bug11035;

use function PHPStan\Testing\assertType;

function phone_number_starts_with_zero(string $phone): string
{
	if (
		(12 === strlen($phone) and '380' === substr($phone, 0, 3)) or
		(11 === strlen($phone) and '80' === substr($phone, 0, 2)) or
		(10 === strlen($phone) and '0' === substr($phone, 0, 1))
	) {
		$phone = '+';
	} else {
		$phone = '';
	}
	return $phone;
}

/**
 * @param int<1,3> $maybeOne
 * @param int<2,10> $neverOne
 */
function lengthTypes(string $phone, int $maybeOne, int $neverOne): string
{
	if (
		10 === strlen($phone)
	) {
		assertType('non-falsy-string', $phone);

		assertType('non-empty-string', substr($phone, 0, 1));
		assertType('bool', '0' === substr($phone, 0, 1));

		assertType('non-empty-string', substr($phone, 0, $maybeOne));
		assertType('bool', '0' === substr($phone, 0, $maybeOne));

		assertType('non-falsy-string', substr($phone, 0, $neverOne));
		assertType('false', '0' === substr($phone, 0, $neverOne));
	}
}
