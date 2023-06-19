<?php // lint >= 8.0

namespace MatchExprRule;

class Foo
{

	/**
	 * @param 1|2|3 $i
	 */
	public function doFoo(int $i): void
	{
		match ($i) {
			'foo' => null, // always false
			default => null,
		};

		match ($i) {
			0 => null,
			1 => null,
			2 => null,
			3 => null, // always true, but do not report (it's the last one)
		};

		match ($i) {
			1 => null,
			2 => null,
			3 => null, // always true - report with strict-rules
			4 => null, // unreachable
		};

		match ($i) {
			1 => null,
			2 => null,
			3 => null, // always true - report with strict-rules
			default => null, // unreachable
		};

		match (1) {
			1 => null, // always true - report with strict-rules
			2 => null, // unreachable
			3 => null, // unreachable
		};

		match (1) {
			1 => null, // always true - report with strict-rules
			default => null, // unreachable
		};

		match ($i) {
			1, 2 => null,
			// unhandled
		};

		match ($i) {
			1, 2 => null,
			default => null, // OK
		};

		match ($i) {
			3, 3 => null, // second 3 is always false
			default => null,
		};

		match (1) {
			1 => 1,
		};

		match ($i) {
			default => 1,
		};

		match ($i) {
			default => 1,
			1 => 2,
		};

		match ($i) {
			// unhandled
		};
	}

	public function doBar(\Exception $e): void
	{
		match (true) {
			$e instanceof \InvalidArgumentException, $e instanceof \InvalidArgumentException => true, // reported by ImpossibleInstanceOfRule
			default => null,
		};

		match (true) {
			$e instanceof \InvalidArgumentException => true,
			$e instanceof \InvalidArgumentException => true, // reported by ImpossibleInstanceOfRule
		};
	}

	/**
	 * @param \stdClass&\Exception $obj
	 */
	public function doBaz($obj): void
	{
		match ($obj) {

		};
	}

	public function doFooConstants(int $i): void
	{

	}

}

class BarConstants
{

	const TEST1 = 1;
	const TEST2 = 2;

	/**
	 * @param BarConstants::TEST1|BarConstants::TEST2 $i
	 */
	public function doFoo(int $i): void {
		match ($i) {
			BarConstants::TEST1 => 'foo',
			BarConstants::TEST2 => 'bar',
		};
	}

	/**
	 * @param BarConstants::TEST* $i
	 */
	public function doBar(int $i): void {
		match ($i) {
			BarConstants::TEST1 => 'foo',
			BarConstants::TEST2 => 'bar',
		};
	}


}

class ThrowsTag {
	/**
	 * @throws \UnhandledMatchError
	 */
	public function foo(int $bar): void
	{
		$str = match($bar) {
			1 => 'test'
		};
	}

	/**
	 * @throws \Error
	 */
	public function bar(int $bar): void
	{
		$str = match($bar) {
			1 => 'test'
		};
	}

	/**
	 * @throws \Exception
	 */
	public function baz(int $bar): void
	{
		$str = match($bar) {
			1 => 'test'
		};
	}
}

function (): string {
	$foo = fn(): int => rand();
	$bar = fn(): int => rand();
	return match ($foo <=> $bar) {
		1 => 'up',
		0 => 'neutral',
		-1 => 'down',
	};
};

final class FinalFoo
{

}

final class FinalBar
{

}

class TestGetClass
{

	public function doMatch(FinalFoo|FinalBar $class): void
	{
		match (get_class($class)) {
			FinalFoo::class => 1,
			FinalBar::class => 2,
		};
	}

}
