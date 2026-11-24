<?php

namespace ArrayMapUnpackedArgs;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{list<string>, list<string>} $tuple
	 * @param list<list<int>> $lists
	 * @param array{array{1}}|array{array{2}, array{3}} $either
	 * @param array{0: array{1}, 1?: array{2}} $optional
	 */
	public function callbackParameters(array $tuple, array $lists, array $either, array $optional): void
	{
		array_map(function ($a, $b) {
			assertType('1|2|3', $a);
			assertType('4|5|6', $b);
		}, [1, 2, 3], ...[[4, 5, 6]]);

		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('3|4', $b);
		}, ...[[1, 2], [3, 4]]);

		array_map(function ($a, $b) {
			assertType('string', $a);
			assertType('string', $b);
		}, ...$tuple);

		array_map(function ($a, $b) {
			assertType('int', $a);
			assertType('int', $b);
		}, ...$lists);

		array_map(function ($a) {
			assertType('1|2', $a);
		}, ...$either);

		array_map(function ($a, $b) {
			assertType('1', $a);
			assertType('2', $b);
		}, ...$optional);
	}

	/**
	 * @param list<list<int>> $lists
	 * @param array{list<string>, list<string>} $tuple
	 */
	public function knownThenUnknown(array $lists, array $tuple): void
	{
		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('int', $b);
		}, [1, 2], ...$lists);

		array_map(function ($a, $b, $c) {
			assertType('1|2', $a);
			assertType('int', $b);
			assertType('int', $c);
		}, ...[[1, 2]], ...$lists);

		array_map(function ($a, $b) {
			assertType('string', $a);
			assertNativeType('mixed', $a);
			assertType('string', $b);
			assertNativeType('mixed', $b);
		}, ...$tuple);

		assertType('list<int>', array_map(fn ($a, $b) => $b, [1, 2], ...$lists));
	}

	/**
	 * @param array{list<string>, list<string>} $tuple
	 * @param list<list<int>> $lists
	 * @param list<array<string, int>> $maps
	 */
	public function returnType(array $tuple, array $lists, array $maps): void
	{
		assertType('non-empty-list<4|5|6>', array_map(fn ($a, $b) => $b, [1, 2, 3], ...[[4, 5, 6]]));
		assertType('array{1, 2}', array_map(fn ($a) => $a, ...[[1, 2]]));
		assertType('non-empty-list<array{1|2, 3|4}>', array_map(null, ...[[1, 2], [3, 4]]));
		assertType('list<string>', array_map(fn ($a, $b) => $a . $b, ...$tuple));
		assertType('list<int>', array_map(fn ($a) => $a, ...$lists));
		assertType('list<int>', array_map(fn (int $a, int $b) => $a + $b, ...$lists));
		assertType('array<int<0, max>|string, int>', array_map(fn (int $a) => $a, ...$maps));
	}

	/**
	 * @param array{0: array{1}, 1?: array{2, 3}} $shorterFirst
	 * @param array{0: array{1, 2, 3}, 1?: array{4}} $shorterRest
	 */
	public function shorterArrayWithUnknownCount(array $shorterFirst, array $shorterRest): void
	{
		array_map(function ($a) {
			assertType('1|null', $a);
		}, ...$shorterFirst);

		array_map(function ($a, $b) {
			assertType('1|2|3', $a);
			assertType('4|null', $b);
		}, ...$shorterRest);

		assertType('non-empty-list<1|null>', array_map(fn ($a) => $a, ...$shorterFirst));
	}

	/**
	 * @param array{0: 1, 1?: 2} $maybePair
	 * @param array{0: array{1}, 1?: array{2}} $maybeTwoArrays
	 */
	public function defaultAfterKnownPositions(array $maybePair, array $maybeTwoArrays): void
	{
		(function ($a, $b = 'fallback') {
			assertType('1', $a);
			assertType('mixed', $b);
		})(...$maybePair);

		(fn ($a, $b = 'fallback') => assertType('mixed', $b))(...$maybePair);

		(function ($a, $b) {
			assertType('2', $b);
		})(...$maybePair);

		(function ($a, $b = 'fallback', ...$rest) {
			assertType('mixed', $b);
		})(...$maybePair);

		array_map(function ($a, $b = 'none') {
			assertType('1', $a);
			assertType('mixed', $b);
		}, ...$maybeTwoArrays);
	}

	/**
	 * @param list<list<int>> $lists
	 * @param array{0: array{1}, 1?: array{2, 3}} $shorterFirst
	 * @param list<array<string, int>> $maps
	 */
	public function genericCallbackWithUnknownCount(array $lists, array $shorterFirst, array $maps): void
	{
		assertType('list<int>', array_map('ArrayMapUnpackedArgs\\identity', ...$lists));
		assertType('non-empty-list<1|null>', array_map('ArrayMapUnpackedArgs\\identity', ...$shorterFirst));
		assertType('array<int<0, max>|string, int>', array_map('ArrayMapUnpackedArgs\\identity', ...$maps));
	}

	/**
	 * @param mixed[][] $arrays
	 * @param list<list<int>> $lists
	 * @param array{0: array{1}, 1?: array{2, 3}} $shorterFirst
	 * @param array{array{1}}|array{array{2}, array{3}} $either
	 * @param list<array{1, 2}> $pairs
	 */
	public function nullCallback(array $arrays, array $lists, array $shorterFirst, array $either, array $pairs): void
	{
		assertType('list<non-empty-list<1|2>>', array_map(null, ...$pairs));
		assertType('list<non-empty-list<mixed>>', array_map(null, ...$arrays));
		assertType('list<non-empty-list<int|null>>', array_map(null, ...$lists));
		assertType('array{1}|non-empty-list<array{1|null, 2|3|null}>', array_map(null, ...$shorterFirst));
		assertType('array{1}|non-empty-list<array{2, 3}>', array_map(null, ...$either));
		assertType('non-empty-list<1|2>', array_map(fn ($a) => $a, ...$either));
	}

	/**
	 * @param array{0: array{1}, 1?: array{2}, 2?: array{3}} $twoOptional
	 * @param array{0: array{1}, 1?: array{2}} $optional
	 */
	public function eachListOfArrays(array $twoOptional, array $optional, string $maybeCallable): void
	{
		assertType('array{1}|non-empty-list<array{0: 1, 1: 2, 2?: 3}>', array_map(null, ...$twoOptional));
		// one array throws an ArgumentCountError, because the callback requires two values
		assertType('non-empty-list<2>', array_map(fn ($a, $b) => $b, ...$optional));
		assertType('non-empty-list', array_map($maybeCallable, ...$optional));
	}

	/**
	 * @param list<array<int, int>> $maybeLists
	 * @param list<list<int>> $lists
	 */
	public function unknownCountEdges(array $maybeLists, array $lists, string $maybeCallable, ?callable $maybeNull): void
	{
		assertType('array<int, int>', array_map(fn ($a) => $a, ...$maybeLists));
		assertType('array', array_map($maybeCallable, ...$lists));
		assertType('array', array_map($maybeNull, ...$lists));
	}

	public function shorterUnpackedArray(): void
	{
		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('3|null', $b);
			assertNativeType('1|2', $a);
			assertNativeType('3|null', $b);
		}, ...[[1, 2], [3]]);

		array_map(fn ($a, $b) => assertType('3|null', $b), [1, 2], ...[[3]]);

		assertType('non-empty-list<1|3>', array_map(fn ($a, $b) => $b ?? 1, ...[[1, 2], [3]]));
	}

	/**
	 * @param list<int> $ints
	 * @param array{0?: 1, 1: 2} $maybeList
	 */
	public function immediatelyInvoked(array $ints, array $maybeList): void
	{
		(function ($a) {
			assertType('1|2', $a);
		})(...$maybeList);

		(function ($a, $b) {
			assertType('int', $a);
			assertType('int', $b);
		})(...$ints);

		(function ($a, $b) {
			assertType('1', $a);
			assertType('2', $b);
		})(...[1, 2]);

		(fn ($a, $b) => assertType('2', $b))(1, ...[2]);
	}

	/**
	 * @param array{array{1}, ...<int, array{2}>} $unsealed
	 * @param array{array{1}, array{2}, ...<int, array{3}>} $unsealedPair
	 */
	public function unsealedShapes(array $unsealed, array $unsealedPair): void
	{
		array_map(function ($a, ...$rest) {
			assertType('1|2', $a);
		}, ...$unsealed);

		assertType('list<1|2>', array_map(fn ($a) => $a, ...$unsealed));
		assertType('list<non-empty-list<1|2|3>>', array_map(null, ...$unsealedPair));

		(function ($a) {
			assertType('array{1}|array{2}', $a);
		})(...$unsealed);
	}

	/**
	 * @param array{0: array{1, 2}, 1?: array{3}, 2?: array{4, 5}} $r3
	 * @param array{array{1}, array{2, 3}}|array{array{4, 5}, array{6}} $crossed
	 * @param list<array{1, 2}|array{3}> $arrays
	 */
	public function arraysOfDifferentLengths(array $r3, array $crossed, array $arrays): void
	{
		array_map(function ($a, $b) {
			assertType('1|2|3|4|5|null', $a);
			assertType('1|2|3|4|5|null', $b);
		}, ...$r3);

		array_map(function ($a, $b) {
			assertType('1|4|5|null', $a);
			assertType('2|3|6|null', $b);
		}, ...$crossed);

		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('1|2|3|null', $b);
		}, [1, 2], ...$arrays);

		assertType('non-empty-list<array{1|null, 2|3|null}|array{4|5|null, 6|null}>', array_map(null, ...$crossed));
		assertType('non-empty-list<array{1|4|5|null, 2|3|6|null}>', array_map(fn ($a, $b) => [$a, $b], ...$crossed));
	}

}

/**
 * @template T
 * @param T $value
 * @return T
 */
function identity($value)
{
	return $value;
}
