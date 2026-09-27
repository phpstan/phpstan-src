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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $inc);
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
		assertType('Closure(): void', $inc);
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
		assertType('Closure(): void', $c);
	}

	public function neverInvoked(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		assertType('1', $x);
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $d);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(1|2): void', $add);
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
		assertType('Closure(list<int>): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
		assertType('Closure(): void', $g);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
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
		assertType('Closure(): void', $c);
	}

	public function parametersAndReturn(): void
	{
		$x = 1;
		$c = function ($p) use (&$x) {
			assertType("2|'a'", $p);
			assertType("1|'a'", $x);
			$x = $p;
			return $x;
		};
		$c('a');
		assertType("'a'", $x);
		$c(2);
		assertType('2', $x);
		assertType("Closure(2|'a'): (2|'a')", $c);
	}

	public function escapesByArrowFunctionCapture(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType("1|'a'", $x);
			$x = 'a';
		};
		$g = fn () => $c();
		$g();
		assertType("1|'a'", $x);
		assertType('Closure(): void', $c);
		assertType('Closure(): void', $g);
	}

	public function arrowFunctionCapturesByValue(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			assertType('1', $x);
			$x = 'a';
		};
		$before = fn () => $x;
		$c();
		$after = fn () => $x;
		assertType("'a'", $x);
		assertType('Closure(): void', $c);
		assertType('Closure(): 1', $before);
		assertType("Closure(): 'a'", $after);
		assertType('1', $before());
		assertType("'a'", $after());
	}

	public function arrowFunctionInvokedWithCapturedValue(): void
	{
		$x = 1;
		$c = function () use (&$x): void {
			$x = 'a';
		};
		$f = fn ($a) => $a;
		assertType('1', $f($x));
		$c();
		assertType("'a'", $f($x));
		assertType("Closure(1|'a'): (1|'a')", $f);
	}

}
