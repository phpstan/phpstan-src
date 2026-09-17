<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug15256Php83;

use Random\Randomizer;
use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonEmptyString
 * @param lowercase-string $lowercaseString
 * @param uppercase-string $uppercaseString
 */
function getBytesFromString(
	Randomizer $randomizer,
	string $nonEmptyString,
	string $lowercaseString,
	string $uppercaseString
): void
{
	assertType('non-empty-string', $randomizer->getBytesFromString($nonEmptyString, 5));
	assertType('lowercase-string&non-empty-string', $randomizer->getBytesFromString($lowercaseString, 5));
	assertType('non-empty-string&uppercase-string', $randomizer->getBytesFromString($uppercaseString, 5));
	assertType('lowercase-string&non-empty-string', $randomizer->getBytesFromString('abc', 5));
}
