<?php

namespace MissingReturn;

class Foo
{

	public function doFoo(): int
	{

	}

	public function doBar(): int
	{
		// noop comment
	}

	public function doBaz(): int
	{
		doFoo();
		doBar();
	}

	public function doLorem(): int
	{
		if (doFoo()) {

		}

		try {

		} catch (\Exception $e) {

		}

		if (doFoo()) {
			doFoo();
			doBar();
			if (doFoo()) {

			} elseif (blabla()) {

			} else {

			}
		} else {
			try {

			} catch (\Exception $e) {

			}
		}
	}

}

class Bar
{

	public function doFoo(): int
	{
		return 1;
	}

	public function doBar(): int
	{
		doFoo();

		return 1;
	}

	public function doBaz(): void
	{

	}

	public function doLorem(): void
	{
		doFoo();
	}

	public function doIpsum()
	{

	}

	public function doDolor()
	{
		doFoo();
	}

	public function doSit(): iterable
	{
		doBar();
		doFoo(yield 1);
	}

}

class Baz
{

	public function doFoo()
	{
		function (): int {

		};
	}

}

function doFoo(): int
{

}

class Yielding
{

	public function doFoo(bool $bool): iterable
	{
		while ($bool) {
			yield 1;
		}
	}

}

class SwitchBranches
{

	public function doFoo(int $i): int
	{
		switch ($i) {
			case 0:
			case 1:
			case 2:
				return 1;
			default:
				return 2;
		}
	}

	public function doBar(int $i): int
	{
		switch ($i) {
			case 0:
				return 0;
			case 1:
				return 1;
			case 2:
				return 2;
		}
	}

	public function doBaz(int $i): int
	{
		switch ($i) {
			case 0:
				return 0;
			case 1:
				return 1;
			case 2:
				return 2;
			default:
				return 3;
		}
	}

	public function doLorem(int $i): int
	{
		switch ($i) {
			case 0:
			case 1:
			case 2:
				return 1;
		}
	}

	public function doIpsum(int $i): int
	{
		switch ($i) {
			case 0:
				return 1;
			case 1:
			case 2:
			default:
		}
	}

	public function doDolor(int $i): int
	{
		switch ($i) {
			case 0:
				return 1;
			case 1:
			case 2:
		}
	}

}

class TryCatchFinally
{

	public function doFoo(): int
	{
		try {

		} catch (\Exception $e) {

		} catch (\Throwable $e) {

		} finally {
			return 1;
		}
	}

	public function doBar(): int
	{
		try {
			return 1;
		} catch (\Exception $e) {
			return 1;
		} catch (\Throwable $e) {
			return 1;
		} finally {

		}
	}

	public function doBaz(): int
	{
		try {
			maybeThrow(); return 1;
		} catch (\Exception $e) {
			return 1;
		} catch (\Throwable $e) {

		}
	}

	public function doLorem(): int
	{
		try {
			return 1;
		} finally {

		}
	}

	public function doIpsum(): int
	{
		try {

		} finally {
			return 1;
		}
	}

	public function doDolor(): int
	{
		try {

		} finally {

		}
	}

}

class MoreYielding
{

	public function doFoo(bool $foo): iterable
	{
		if ($foo) {
			yield 1;
		}
	}

}

class ReturnInPhpDoc
{

	/**
	 * @return int
	 */
	public function doFoo()
	{

	}

}

class YieldInAssign
{

	public function doFoo(\Generator $items): \Generator
	{
		while ($items->valid()) {
			$item = $items->current();

			$state = yield $item;

			$items->send($state);
		}
	}

}

class FooTemplateMixedType
{

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	public function doFoo($a)
	{

	}

}

class MissingReturnGenerators
{

	public function emptyBodyUnspecifiedTReturn(): \Generator
	{

	}

	public function bodyUnspecifiedTReturn(): \Generator
	{
		yield 1;
	}

	/**
	 * @return \Generator<int, int>
	 */
	public function emptyBodyUnspecifiedTReturn2(): \Generator
	{

	}

	/**
	 * @return \Generator<int, int>
	 */
	public function bodyUnspecifiedTReturn2(): \Generator
	{
		yield 1;
	}

	/**
	 * @return \Generator<int, int, int, string>
	 */
	public function emptyBodySpecifiedTReturn(): \Generator
	{

	}

	/**
	 * @return \Generator<int, int, int, string>
	 */
	public function bodySpecifiedTReturn(): \Generator
	{
		yield 1;
	}

	/**
	 * @return \Generator<int, int, int, void>
	 */
	public function bodySpecifiedVoidTReturn(): \Generator
	{
		yield 1;
	}

	/**
	 * @return \Generator<int, int, int, void>
	 */
	public function bodySpecifiedVoidTReturn2(): \Generator
	{
		yield 1;
		return;
	}

	/**
	 * @return \Generator<int, int, int, void>
	 */
	public function bodySpecifiedVoidTReturn3(): \Generator
	{
		yield 1;
		return 2;
	}

	public function yieldInWhileCondition(): \Generator
	{
		while($foo = yield 'foo') {
		}
	}

	public function yieldInForCondition(): \Generator
	{
		for($foo = 0; $foo > 0; $foo = yield 1) {
		}
	}

	public function yieldInDoCondition(): \Generator
	{
		do {
		} while(yield 1);
	}

	public function yieldInForeach(): \Generator
	{
		foreach (yield 1 as $bar) {
		}
	}

	public function yieldInIf(): \Generator
	{
		if (yield 1) {
		}
	}

	public function yieldInSwitch(): \Generator
	{
		switch (yield 1) {
			default:
		}
	}

}

class VoidUnion
{

	/**
	 * @return int|void
	 */
	public function doFoo()
	{
		echo 'test';
	}

}

class NeverReturn
{

	/**
	 * @return never
	 */
	public function doFoo()
	{
		throw new \Exception();
	}

	/**
	 * @return never
	 */
	public function doBar()
	{
		die;
	}

	/**
	 * @return never
	 */
	public function doBaz()
	{

	}

	/**
	 * @return never
	 */
	public function doBaz2(): array
	{

	}

}

class ClosureWithMissingReturnWithoutTypehint
{

	public function doFoo(): void
	{
		function () {
			if (rand(0, 1)) {
				return;
			}
		};

		function () {
			if (rand(0, 1)) {
				return null;
			}
		};
	}

}

class MorePreciseMissingReturnLines
{

	public function doFoo(): int
	{
		if (doFoo()) {
			echo 1;
		} elseif (doBar()) {

		} else {
			return 1;
		}
	}

	public function doFoo2(): int
	{
		if (doFoo()) {
			return 1;
		} elseif (doBar()) {
			return 2;
		}
	}

}
