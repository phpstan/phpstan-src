<?php

namespace ReportUnsafeArrayStringKeyCastingDetect;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, self> $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array<string, ReportUnsafeArrayStringKeyCastingDetect\Foo>', $a);
		foreach ($a as $k => $v) {
			assertType('int|non-decimal-int-string', $k);
		}
	}

	/**
	 * @param array<self> $a
	 */
	public function doBar(array $a): void
	{
		assertType('array<ReportUnsafeArrayStringKeyCastingDetect\Foo>', $a);
		foreach ($a as $k => $v) {
			assertType('(int|non-decimal-int-string)', $k);
		}
	}

	/**
	 * @param array<int|string, self> $a
	 */
	public function doBaz(array $a): void
	{
		assertType('array<int|string, ReportUnsafeArrayStringKeyCastingDetect\Foo>', $a);
		foreach ($a as $k => $v) {
			assertType('int|non-decimal-int-string', $k);
		}
	}

	public function doArrayCreationAndAssign(string $s): void
	{
		$a = [$s => 1];
		assertType('non-empty-array<string, 1>', $a);

		$b = [];
		$b[$s] = 2;
		assertType('non-empty-array<string, 2>', $b);
	}

}

class FooNonDecimalIntString
{

	/**
	 * @param array<non-decimal-int-string, self> $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array<non-decimal-int-string, ReportUnsafeArrayStringKeyCastingDetect\FooNonDecimalIntString>', $a);
		foreach ($a as $k => $v) {
			assertType('non-decimal-int-string', $k);
		}
	}

	/**
	 * @param array<int|non-decimal-int-string, self> $a
	 */
	public function doBaz(array $a): void
	{
		assertType('array<int|non-decimal-int-string, ReportUnsafeArrayStringKeyCastingDetect\FooNonDecimalIntString>', $a);
		foreach ($a as $k => $v) {
			assertType('int|non-decimal-int-string', $k);
		}
	}

	/** @param non-decimal-int-string $s */
	public function doArrayCreationAndAssign(string $s): void
	{
		$a = [$s => 1];
		assertType('non-empty-array<non-decimal-int-string, 1>', $a);

		$b = [];
		$b[$s] = 2;
		assertType('non-empty-array<non-decimal-int-string, 2>', $b);
	}

}

class Unsealed
{

	/**
	 * @param array{a: self, ...<string, self>} $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array{a: ReportUnsafeArrayStringKeyCastingDetect\Unsealed, ...<string, ReportUnsafeArrayStringKeyCastingDetect\Unsealed>}', $a);
		foreach ($a as $k => $v) {
			assertType('int|non-decimal-int-string', $k);
		}
	}

	/**
	 * @param array{a: self, ...<self>} $a
	 */
	public function doBar(array $a): void
	{
		assertType('array{a: ReportUnsafeArrayStringKeyCastingDetect\Unsealed, ...<ReportUnsafeArrayStringKeyCastingDetect\Unsealed>}', $a);
		foreach ($a as $k => $v) {
			assertType('(int|non-decimal-int-string)', $k);
		}
	}

	/**
	 * @param array{a: self, ...<int|string, self>} $a
	 */
	public function doBaz(array $a): void
	{
		assertType('array{a: ReportUnsafeArrayStringKeyCastingDetect\Unsealed, ...<int|string, ReportUnsafeArrayStringKeyCastingDetect\Unsealed>}', $a);
		foreach ($a as $k => $v) {
			assertType('int|non-decimal-int-string', $k);
		}
	}

}

class ReadKeys
{

	/**
	 * @param array<string, int> $a
	 * @param non-empty-array<string, int> $b
	 */
	public function doFoo(array $a, array $b, string $s): void
	{
		assertType('int|non-decimal-int-string', array_key_first([$s => null]));
		assertType('int|non-decimal-int-string', array_key_last([$s => null]));
		assertType('non-empty-list<int|non-decimal-int-string>', array_keys([$s => null]));

		assertType('int|non-decimal-int-string|null', array_key_first($a));
		assertType('int|non-decimal-int-string', array_key_last($b));
		assertType('list<int|non-decimal-int-string>', array_keys($a));
		assertType('int|non-decimal-int-string|null', key($a));
		assertType('int|non-decimal-int-string|false', array_search(1, $a, true));
	}

}
