<?php // lint >= 8.4

namespace RoundFamilyTestPHP84;

use function PHPStan\Testing\assertType;

function constantRoundingMode(): void
{
	assertType('10.0', round(9.5, 0, \RoundingMode::HalfAwayFromZero));
	assertType('9.0', round(9.5, 0, \RoundingMode::HalfTowardsZero));
	assertType('10.0', round(9.5, 0, \RoundingMode::HalfEven));
	assertType('9.0', round(9.5, 0, \RoundingMode::HalfOdd));

	assertType('float', round(9.5, 0, \RoundingMode::TowardsZero));
	assertType('float', round(9.5, 0, \RoundingMode::AwayFromZero));
	assertType('float', round(9.5, 0, \RoundingMode::NegativeInfinity));
	assertType('float', round(9.5, 0, \RoundingMode::PositiveInfinity));

    assertType('10.0', round(9.5, mode: \RoundingMode::HalfAwayFromZero));
}
