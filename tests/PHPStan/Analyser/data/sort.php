<?php declare(strict_types=1);

namespace Sort;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;
use function sort;

class Foo
{
	public function constantArray(): void
	{
		$arr = [
			4,
			'one' => 1,
			'five' => 5,
			'three' => 3,
		];

		$arr1 = $arr;
		sort($arr1);
		assertType('non-empty-list<1|3|4|5>', $arr1);
		assertNativeType('non-empty-list<1|3|4|5>', $arr1);

		$arr2 = $arr;
		rsort($arr2);
		assertType('non-empty-list<1|3|4|5>', $arr2);
		assertNativeType('non-empty-list<1|3|4|5>', $arr2);

		$arr3 = $arr;
		usort($arr3, fn(int $a, int $b) => $a <=> $b);
		assertType('non-empty-list<1|3|4|5>', $arr3);
		assertNativeType('non-empty-list<1|3|4|5>', $arr3);
	}

	public function constantArrayOptionalKey(): void
	{
		$arr = [
			'one' => 1,
			'five' => 5,
		];
		if (rand(0, 1)) {
			$arr['two'] = 2;
		}

		$arr1 = $arr;
		sort($arr1);
		assertType('non-empty-list<1|2|5>', $arr1);
		assertNativeType('non-empty-list<1|2|5>', $arr1);

		$arr2 = $arr;
		rsort($arr2);
		assertType('non-empty-list<1|2|5>', $arr2);
		assertNativeType('non-empty-list<1|2|5>', $arr2);

		$arr3 = $arr;
		usort($arr3, fn(int $a, int $b) => $a <=> $b);
		assertType('non-empty-list<1|2|5>', $arr3);
		assertNativeType('non-empty-list<1|2|5>', $arr3);
	}

	public function constantArrayUnion(): void
	{
		$arr = rand(0, 1) ? [
			'one' => 1,
			'five' => 5,
		] : [
			'two' => 2,
		];

		$arr1 = $arr;
		sort($arr1);
		assertType('non-empty-list<1|2|5>', $arr1);
		assertNativeType('non-empty-list<1|2|5>', $arr1);

		$arr2 = $arr;
		rsort($arr2);
		assertType('non-empty-list<1|2|5>', $arr2);
		assertNativeType('non-empty-list<1|2|5>', $arr2);

		$arr3 = $arr;
		usort($arr3, fn(int $a, int $b) => $a <=> $b);
		assertType('non-empty-list<1|2|5>', $arr3);
		assertNativeType('non-empty-list<1|2|5>', $arr3);
	}

	/** @param array<mixed, string> $arr */
	public function normalArray(array $arr): void
	{
		$arr1 = $arr;
		sort($arr1);
		assertType('list<string>', $arr1);
		assertNativeType('list<mixed>', $arr1);

		$arr2 = $arr;
		rsort($arr2);
		assertType('list<string>', $arr2);
		assertNativeType('list<mixed>', $arr2);

		$arr3 = $arr;
		usort($arr3, fn(int $a, int $b) => $a <=> $b);
		assertType('list<string>', $arr3);
		assertNativeType('list<mixed>', $arr3);
	}

	public function mixed($arr): void
	{
		$arr1 = $arr;
		sort($arr1);
		assertType('mixed', $arr1);
		assertNativeType('mixed', $arr1);

		$arr2 = $arr;
		rsort($arr2);
		assertType('mixed', $arr2);
		assertNativeType('mixed', $arr2);

		$arr3 = $arr;
		usort($arr3, fn(int $a, int $b) => $a <=> $b);
		assertType('mixed', $arr3);
		assertNativeType('mixed', $arr3);
	}

	public function notArray(): void
	{
		$arr = 'foo';
		sort($arr);
		assertType("'foo'", $arr);
	}
}
