<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug12912;

use function array_map;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

enum Bar: string
{
	case Yes = 'yes';
	case No = 'no';
}

class Foo
{

	protected Bar $foo = Bar::Yes;

	protected ?Foo $nested = null;

	protected ?Bar $nullableFoo = null;

	public function __construct(public readonly ?Bar $readonlyFoo = null)
	{
	}

	public function foo(): void
	{
		if ($this->foo === Bar::No) {
			return;
		}

		assertType('Bug12912\Bar::Yes', $this->foo);

		$i = $this->wrap(fn() => assertType('Bug12912\Bar::Yes', $this->foo));

		$i = $this->wrap(function () {
			assertType('Bug12912\Bar::Yes', $this->foo);
		});
	}

	public function immediatelyInvokedClosure(): void
	{
		if ($this->foo === Bar::No) {
			return;
		}

		(function () {
			assertType('Bug12912\Bar::Yes', $this->foo);
		})();
	}

	public function builtInFunctionCallback(): void
	{
		if ($this->foo === Bar::No) {
			return;
		}

		array_map(function (int $i) {
			assertType('Bug12912\Bar::Yes', $this->foo);

			return $i;
		}, [1, 2, 3]);
	}

	public function laterInvokedClosure(): void
	{
		if ($this->foo === Bar::No) {
			return;
		}
		if ($this->readonlyFoo === null) {
			return;
		}

		// the callback may run after the property has been reassigned
		$this->wrapLater(function () {
			assertType('Bug12912\Bar', $this->foo);
			assertType('Bug12912\Bar', $this->readonlyFoo);
		});
	}

	public function nestedPropertyFetch(): void
	{
		if ($this->nested === null || $this->nested->nullableFoo === null) {
			return;
		}

		$i = $this->wrap(function () {
			assertType('Bug12912\Bar', $this->nested->nullableFoo);
		});
	}

	public function methodCall(): void
	{
		if ($this->getFoo() === null) {
			return;
		}

		$i = $this->wrap(function () {
			assertType('Bug12912\Bar', $this->getFoo());
		});
	}

	public function conditionalExpression(): void
	{
		if ($this->nullableFoo === null) {
			$y = 1;
		} else {
			$y = 'str';
		}

		$i = $this->wrap(function () use ($y) {
			if ($this->nullableFoo !== null) {
				assertType("'str'", $y);
			}
		});
	}

	public function conditionalExpressionOnUsedVariable(Foo $other): void
	{
		if ($other->nullableFoo === null) {
			$y = 1;
		} else {
			$y = 'str';
		}

		// works no matter when the callback is invoked - $other is captured by value
		$this->wrapLater(function () use ($y, $other) {
			if ($other->nullableFoo !== null) {
				assertType("'str'", $y);
			}
		});
	}

	public function nativeTypes(Foo $other): void
	{
		if ($this->nullableFoo === null) {
			return;
		}
		if ($other->nullableFoo === null) {
			return;
		}

		$i = $this->wrap(function () {
			assertNativeType('Bug12912\Bar', $this->nullableFoo);
		});

		$this->wrapLater(function () use ($other) {
			assertNativeType('Bug12912\Bar', $other->nullableFoo);
		});
	}

	public function staticClosureDoesNotSeeThis(): void
	{
		if ($this->foo === Bar::No) {
			return;
		}

		$i = $this->wrap(static function () {
			assertType('*ERROR*', $this);
		});
	}

	/**
	 * @phpstan-pure
	 */
	public function getFoo(): ?Bar
	{
		return $this->nullableFoo;
	}

	/**
	 * @phpstan-pure
	 * @param-immediately-invoked-callable $callback
	 */
	public function wrap(callable $callback): int
	{
		$callback();

		return 1;
	}

	/**
	 * @param-later-invoked-callable $callback
	 */
	public function wrapLater(callable $callback): void
	{
	}

}
