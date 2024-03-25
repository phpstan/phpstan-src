<?php // lint >= 7.4

namespace ParamClosureThis;

use function PHPStan\Testing\assertType;
use function sprintf;

interface Some
{

	public function voidMethod(): void;

}

class Foo
{

	public ?string $prop = null;

	/**
	 * @param-closure-this Some $cb
	 */
	public function paramClosureClass(callable $cb)
	{

	}

	/**
	 * @param-closure-this self $cb
	 */
	public function paramClosureSelf(callable $cb)
	{

	}

	/**
	 * @param-closure-this Some $cb
	 * @param-immediately-invoked-callable $cb
	 */
	public function paramClosureClassImmediatelyCalled(callable $cb)
	{

	}

	/**
	 * @param-closure-this static $cb
	 */
	public function paramClosureStatic(callable $cb)
	{

	}

	/**
	 * @param-closure-this ($i is 1 ? Foo : Some) $cb
	 */
	public function paramClosureConditional(int $i, callable $cb)
	{

	}

	public function voidMethod(): void
	{

	}

	public function doFoo(): void
	{
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(function () {
			assertType(Some::class, $this);
		});
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(static function () {
			assertType('*ERROR*', $this);
		});
		assertType(sprintf('$this(%s)', self::class), $this);

		$this->paramClosureSelf(function () use (&$a) {
			assertType(Foo::class, $this);
		});
		$this->paramClosureConditional(1, function () {
			assertType(Foo::class, $this);
		});
		$this->paramClosureConditional(2, function () {
			assertType(Some::class, $this);
		});
	}

	public function doFoo2(): void
	{
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(fn () => assertType(Some::class, $this));
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(static fn () => assertType('*ERROR*', $this));
		assertType(sprintf('$this(%s)', self::class), $this);
	}

	public function doFoo3(): void
	{
		$a = 1;
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(function () use (&$a) {
			assertType(Some::class, $this);
		});
		assertType(sprintf('$this(%s)', self::class), $this);
		$this->paramClosureClass(static function () use (&$a) {
			assertType('*ERROR*', $this);
		});
		assertType(sprintf('$this(%s)', self::class), $this);
	}

	public function interplayWithProcessImmediatelyCalledCallable(): void
	{
		assert($this->prop !== null);
		assertType('string', $this->prop);
		$this->paramClosureClassImmediatelyCalled(function () {
			// $this is Some, not Foo
			$this->voidMethod();
		});

		// keep the narrowed type
		assertType('string', $this->prop);
	}

	public function interplayWithProcessImmediatelyCalledCallable2(): void
	{
		$s = new self();
		assert($s->prop !== null);
		assertType('string', $s->prop);
		$this->paramClosureClassImmediatelyCalled(function () use ($s) {
			// $this is Some, not Foo
			$this->voidMethod();

			// but still invalidate $s
			$s->voidMethod();
		});
		assertType('string|null', $s->prop);
	}

}

function (Foo $f): void {
	assertType('*ERROR*', $this);
	$f->paramClosureClass(function () {
		assertType(Some::class, $this);
	});
	assertType('*ERROR*', $this);
	$f->paramClosureClass(static function () {
		assertType('*ERROR*', $this);
	});
	assertType('*ERROR*', $this);
};

function (Foo $f): void {
	$a = 1;
	assertType('*ERROR*', $this);
	$f->paramClosureClass(function () use (&$a) {
		assertType(Some::class, $this);
	});
	assertType('*ERROR*', $this);
	$f->paramClosureClass(static function () use (&$a) {
		assertType('*ERROR*', $this);
	});
	assertType('*ERROR*', $this);
};

function (Foo $f): void {
	assertType('*ERROR*', $this);
	$f->paramClosureClass(fn () => assertType(Some::class, $this));
	assertType('*ERROR*', $this);
	$f->paramClosureClass(static fn () => assertType('*ERROR*', $this));
	assertType('*ERROR*', $this);
};

class Bar extends Foo
{

	public function testClosureStatic(): void
	{
		assertType('$this(ParamClosureThis\Bar)', $this);
		$this->paramClosureStatic(function () {
			assertType('static(ParamClosureThis\Bar)', $this);
		});
		assertType('$this(ParamClosureThis\Bar)', $this);
	}

}

function (Bar $b): void {
	$b->paramClosureStatic(function () {
		assertType(Bar::class, $this);
	});
};
