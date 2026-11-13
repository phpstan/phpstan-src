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

		assertType('array', array_map(fn ($a, $b) => $b, [1, 2], ...$lists));
	}

	/**
	 * @param array{list<string>, list<string>} $tuple
	 * @param list<list<int>> $lists
	 */
	public function returnType(array $tuple, array $lists): void
	{
		assertType('non-empty-list<4|5|6>', array_map(fn ($a, $b) => $b, [1, 2, 3], ...[[4, 5, 6]]));
		assertType('array{1, 2}', array_map(fn ($a) => $a, ...[[1, 2]]));
		assertType('non-empty-list<array{1|2, 3|4}>', array_map(null, ...[[1, 2], [3, 4]]));
		assertType('list<string>', array_map(fn ($a, $b) => $a . $b, ...$tuple));
		assertType('array', array_map(fn ($a) => $a, ...$lists));
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

}
