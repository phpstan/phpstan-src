<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15256;

use Random\Engine\Mt19937;
use Random\Engine\PcgOneseq128XslRr64;
use Random\Engine\Secure;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use function PHPStan\Testing\assertType;

class Shuffle
{

	/**
	 * @template T
	 * @param T[] $array
	 * @return list<T>
	 */
	public static function shuffle(array $array): array
	{
		$randomizer = new Randomizer();
		assertType('list<T (method Bug15256\Shuffle::shuffle(), argument)>', $randomizer->shuffleArray($array));

		return $randomizer->shuffleArray($array);
	}

	/**
	 * @template TKey of array-key
	 * @param non-empty-array<TKey, mixed> $array
	 * @return non-empty-list<TKey>
	 */
	public static function pick3ArrayKeys(array $array): array
	{
		$randomizer = new Randomizer();
		assertType('non-empty-list<TKey of (int|string) (method Bug15256\Shuffle::pick3ArrayKeys(), argument)>', $randomizer->pickArrayKeys($array, 3));

		return $randomizer->pickArrayKeys($array, 3);
	}

}

/**
 * @param non-empty-array<string, int> $nonEmptyArray
 * @param list<int> $list
 * @param array<int|string, bool> $mixedKeys
 * @param non-empty-array<numeric-string, int> $numericStringKeys
 * @param non-empty-array<decimal-int-string, int> $decimalIntStringKeys
 * @param non-empty-array<non-decimal-int-string, int> $nonDecimalIntStringKeys
 * @param positive-int $num
 */
function arrayMethods(
	Randomizer $randomizer,
	array $nonEmptyArray,
	array $list,
	array $mixedKeys,
	array $numericStringKeys,
	array $decimalIntStringKeys,
	array $nonDecimalIntStringKeys,
	int $num
): void
{
	assertType('non-empty-list<int>', $randomizer->shuffleArray($nonEmptyArray));
	assertType('list<int>', $randomizer->shuffleArray($list));
	assertType('non-empty-list<1|2>', $randomizer->shuffleArray(['a' => 1, 'b' => 2]));
	assertType('array{}', $randomizer->shuffleArray([]));
	assertType('list<bool>', $randomizer->shuffleArray($mixedKeys));

	assertType('non-empty-list<string>', $randomizer->pickArrayKeys($nonEmptyArray, 2));
	assertType('non-empty-list<int<0, max>>', $randomizer->pickArrayKeys($list, 2));
	assertType('non-empty-list<int|string>', $randomizer->pickArrayKeys($mixedKeys, 2));
	assertType("non-empty-list<'a'|'b'>", $randomizer->pickArrayKeys(['a' => 1, 'b' => 2], 2));

	// Numeric string keys are cast to integers, just like array_rand() describes them.
	assertType('non-empty-list<int|numeric-string>', $randomizer->pickArrayKeys($numericStringKeys, 2));
	assertType('non-empty-list<1|2>', $randomizer->pickArrayKeys(['1' => 'a', '2' => 'b'], 2));
	assertType("non-empty-list<1|'b'>", $randomizer->pickArrayKeys(['1' => 'a', 'b' => 'b'], 2));

	// A decimal-int-string key is always cast to an integer, a non-decimal-int-string one never is.
	assertType('non-empty-list<int>', $randomizer->pickArrayKeys($decimalIntStringKeys, 2));
	assertType('non-empty-list<non-decimal-int-string>', $randomizer->pickArrayKeys($nonDecimalIntStringKeys, 2));
	assertType('array{int}', $randomizer->pickArrayKeys($decimalIntStringKeys, 1));
	assertType('array{non-decimal-int-string}', $randomizer->pickArrayKeys($nonDecimalIntStringKeys, 1));

	// Unlike array_rand(), picking a single key still returns an array.
	assertType('array{string}', $randomizer->pickArrayKeys($nonEmptyArray, 1));
	assertType("array{'a'}|array{'b'}", $randomizer->pickArrayKeys(['a' => 1, 'b' => 2], 1));
	assertType('non-empty-list<string>', $randomizer->pickArrayKeys($nonEmptyArray, $num));
}

/**
 * @param non-empty-string $nonEmptyString
 * @param non-falsy-string $nonFalsyString
 * @param lowercase-string $lowercaseString
 * @param uppercase-string $uppercaseString
 */
function stringMethods(
	Randomizer $randomizer,
	string $string,
	string $nonEmptyString,
	string $nonFalsyString,
	string $lowercaseString,
	string $uppercaseString
): void
{
	assertType('string', $randomizer->shuffleBytes($string));
	assertType('non-empty-string', $randomizer->shuffleBytes($nonEmptyString));
	assertType('non-falsy-string', $randomizer->shuffleBytes($nonFalsyString));
	assertType('lowercase-string', $randomizer->shuffleBytes($lowercaseString));
	assertType('uppercase-string', $randomizer->shuffleBytes($uppercaseString));
	assertType('lowercase-string&non-falsy-string', $randomizer->shuffleBytes('abc'));

	assertType('non-empty-string', $randomizer->getBytes(5));
}

function numberMethods(Randomizer $randomizer, int $int): void
{
	assertType('int<1, 10>', $randomizer->getInt(1, 10));
	assertType('int<0, max>', $randomizer->getInt(0, $int));
	assertType('int<0, max>', $randomizer->nextInt());
}

function engines(): void
{
	assertType('non-empty-string', (new Mt19937())->generate());
	assertType('non-empty-string', (new PcgOneseq128XslRr64())->generate());
	assertType('non-empty-string', (new Secure())->generate());
	assertType('non-empty-string', (new Xoshiro256StarStar())->generate());
}
