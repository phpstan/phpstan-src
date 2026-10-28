<?php

namespace Bug13217;

use function PHPStan\Testing\assertType;

class Foo
{

	public function shorterSecondArray(): void
	{
		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('3|null', $b);
		}, [1, 2], [3]);

		array_map(fn ($a, $b) => assertType('3|null', $b), [1, 2], [3]);

		assertType('non-empty-list<1|3>', array_map(fn ($a, $b) => $b ?? 1, [1, 2], [3]));
	}

	public function shorterFirstArray(): void
	{
		array_map(function ($a, $b) {
			assertType('1|null', $a);
			assertType('3|4', $b);
		}, [1], [3, 4]);
	}

	public function sameSize(): void
	{
		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('3|4', $b);
		}, [1, 2], [3, 4]);
	}

	/**
	 * @param array{1, 2}|array{1} $maybe
	 */
	public function sizeRange(array $maybe): void
	{
		array_map(function ($a, $b) {
			assertType('1|2|null', $a);
			assertType('3|4|5', $b);
		}, $maybe, [3, 4, 5]);

		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('3|null', $b);
		}, $maybe, [3]);

		array_map(function ($a, $b) {
			assertType('1|2', $a);
			assertType('1|2', $b);
		}, $maybe, $maybe);
	}

	public function unpacked(): void
	{
		// the closure gets one parameter per arg here, not one per unpacked array
		array_map(function ($a, $b) {
			assertType('1|2|3', $a);
			assertType('array{4, 5, 6}', $b);
		}, [1, 2, 3], ...[[4, 5, 6]]);
	}

	public function singleArray(): void
	{
		array_map(function ($a) {
			assertType('1|2', $a);
		}, [1, 2]);
	}

	/**
	 * @param list<int> $ints
	 * @param list<string> $strings
	 */
	public function generalArrays(array $ints, array $strings): void
	{
		array_map(function ($a, $b) {
			assertType('int', $a);
			assertType('string', $b);
		}, $ints, $strings);

		array_map(function ($a) {
			assertType('int', $a);
		}, $ints);

		array_map(function ($a, $b) {
			assertType('int', $a);
			assertType('3', $b);
		}, $ints, [3]);
	}

	public function returnType(): void
	{
		assertType('non-empty-list<3|null>', array_map('Bug13217\\pick', [1, 2], [3]));
	}

}

/**
 * @template T
 * @param mixed $a
 * @param T $b
 * @return T
 */
function pick($a, $b)
{
	return $b;
}
