<?php // lint >= 8.0

namespace ClosureByRefUsesAtInvocation;

use function PHPStan\Testing\assertType;

function takesCallable(callable $cb): void
{
}

function mayThrow(): void
{
}

class Foo
{

	/** @var callable|null */
	public $callback;

	public function invokedOnce(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		assertType('1', $x);
		$c();
		assertType("'a'", $x);
	}

	public function invokedTwice(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		$c();
		$c();
		assertType("'a'", $x);
	}

	public function incrementedTwice(): void
	{
		$i = 0;
		$inc = function () use (&$i): void {
			assertType('0|1', $i);
			$i++;
		};
		$inc();
		$inc();
		assertType('2', $i);
	}

	public function incrementedInLoop(): void
	{
		$i = 0;
		$inc = function () use (&$i): void {
			assertType('int<0, max>', $i);
			$i++;
		};
		for ($j = 0; $j < 10; $j++) {
			$inc();
		}
		assertType('int<1, max>', $i);
	}

	public function invokedConditionally(bool $b): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		if ($b) {
			$c();
		}
		assertType("1|'a'", $x);
	}

	public function neverInvoked(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		assertType('1', $x);
	}

	public function modifiedBeforeInvocation(): void
	{
		$y = 1;
		$d = function () use (&$y): void {
			assertType("'b'", $y);
		};
		$y = 'b';
		$d();
		assertType("'b'", $y);
	}

	public function undefinedBeforeCreation(): void
	{
		$c = function () use (&$x): void {
			assertType('null', $x);
			$x = 'a';
		};
		assertType('null', $x);
		$c();
		assertType("'a'", $x);
	}

	public function byValueCaptureAtCreation(): void
	{
		$v = 1;
		$x = null;
		$c = function () use ($v, &$x): void {
			assertType('1', $v);
			$x = $v;
		};
		$v = 2;
		$c();
		assertType('1', $x);
	}

	public function accumulator(): void
	{
		$items = [];
		$add = function (int $item) use (&$items): void {
			$items[] = $item;
		};
		$add(1);
		$add(2);
		assertType('array{1, 2}', $items);
	}

	/** @param list<int> $values */
	public function bodyWithLoop(array $values): void
	{
		$sum = 0;
		$c = function (array $values) use (&$sum): void {
			foreach ($values as $value) {
				$sum += 1;
			}
		};
		$c($values);
		assertType('int<0, max>', $sum);
	}

	public function catchAfterInvocation(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			$x = 'a';
			mayThrow();
			$x = 'b';
		};
		try {
			$c();
		} catch (\Exception $e) {
			assertType("1|'a'", $x);
		}
	}

	public function escapesAsArgument(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		takesCallable($c);
		assertType("1|'a'", $x);
	}

	public function escapesToProperty(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		$this->callback = $c;
		assertType("1|'a'", $x);
	}

	public function escapesByCapture(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		$g = function () use ($c): void {
			$c();
		};
		$g();
		assertType("1|'a'", $x);
	}

	public function escapedButAlsoInvokedLocally(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|2|'a'", $x);
			$x = 'a';
		};
		takesCallable($c);
		$x = 2;
		$c();
		assertType("2|'a'", $x);
	}

	public function immediatelyInvoked(): void
	{
		$x = 1;
		(function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		})();
		assertType("'a'", $x);
	}

	public function invokedBeforeThrow(bool $b): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
		};
		if ($b) {
			$x = 'a';
			$c();
			throw new \Exception();
		}
		$c();
	}

	public function invokedBeforeReturn(bool $b): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
		};
		if ($b) {
			$x = 'a';
			$c();
			return;
		}
		$c();
	}

	public function invokedInTry(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			mayThrow();
		};
		try {
			$c();
			$x = 'a';
			$c();
		} catch (\Exception $e) {
			assertType("1|'a'", $x);
		}
	}

	public function invokedInSwitchBreak(int $i): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|2|3", $x);
		};
		switch ($i) {
			case 1:
				$x = 2;
				$c();
				break;
			case 2:
				$x = 3;
				$c();
				exit();
			default:
				$c();
		}
	}

	/** @param list<bool> $a */
	public function invokedInLoopBreak(array $a): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
		};
		foreach ($a as $v) {
			if ($v) {
				$x = 'a';
				$c();
				continue;
			}
			$c();
			break;
		}
	}

}
