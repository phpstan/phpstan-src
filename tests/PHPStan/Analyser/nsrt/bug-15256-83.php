<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug15256Php83;

use Random\IntervalBoundary;
use Random\Randomizer;
use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonEmptyString
 * @param lowercase-string $lowercaseString
 */
function getBytesFromString(
	Randomizer $randomizer,
	string $nonEmptyString,
	string $lowercaseString
): void
{
	assertType('non-empty-string', $randomizer->getBytesFromString($nonEmptyString, 5));
	assertType('non-empty-string', $randomizer->getBytesFromString($lowercaseString, 5));
	assertType('non-empty-string', $randomizer->getBytesFromString('abc', 5));
}

// PHPStan has no float range types, so there is nothing more precise to say
// about these than the float the class declares.
function floatMethods(Randomizer $randomizer, float $float): void
{
	assertType('float', $randomizer->nextFloat());
	assertType('float', $randomizer->getFloat(0.0, 1.0));
	assertType('float', $randomizer->getFloat($float, $float, IntervalBoundary::ClosedClosed));
}
