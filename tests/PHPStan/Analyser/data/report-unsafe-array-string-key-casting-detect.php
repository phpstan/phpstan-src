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
