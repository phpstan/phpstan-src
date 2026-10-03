<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15256Shadowed;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-array<string, int> $array
 * @return array{'shadowed'}
 */
function array_rand(array $array, int $num = 1): array
{
	return ['shadowed'];
}

/**
 * @return 'shadowed'
 */
function str_shuffle(string $string): string
{
	return 'shadowed';
}

/**
 * @return 999
 */
function random_int(int $min, int $max): int
{
	return 999;
}

class Randomizer
{

	/**
	 * @param array<int|string, mixed> $array
	 * @return array<int|string>
	 */
	public function pickArrayKeys(array $array, int $num): array
	{
		return [];
	}

	public function shuffleBytes(string $bytes): string
	{
		return $bytes;
	}

	public function getInt(int $min, int $max): int
	{
		return $min;
	}

}

/**
 * The extension builds the calls it borrows types from with fully qualified names,
 * so functions of the same name in the current namespace do not take over.
 *
 * @param non-empty-array<string, int> $nonEmptyArray
 * @param non-empty-string $nonEmptyString
 */
function shadowedFunctions(
	\Random\Randomizer $randomizer,
	array $nonEmptyArray,
	string $nonEmptyString
): void
{
	assertType("array{'shadowed'}", array_rand($nonEmptyArray, 2));
	assertType("'shadowed'", str_shuffle($nonEmptyString));
	assertType('999', random_int(1, 10));

	assertType('array{(int|string), (int|string)}', $randomizer->pickArrayKeys($nonEmptyArray, 2));
	assertType('non-empty-string', $randomizer->shuffleBytes($nonEmptyString));
	assertType('int<1, 10>', $randomizer->getInt(1, 10));
}

/**
 * A class of the same name in another namespace is not described by the extension.
 *
 * @param non-empty-array<string, int> $nonEmptyArray
 * @param non-empty-string $nonEmptyString
 */
function shadowedClass(
	Randomizer $randomizer,
	array $nonEmptyArray,
	string $nonEmptyString
): void
{
	assertType('array<int|string>', $randomizer->pickArrayKeys($nonEmptyArray, 2));
	assertType('string', $randomizer->shuffleBytes($nonEmptyString));
	assertType('int', $randomizer->getInt(1, 10));
}
