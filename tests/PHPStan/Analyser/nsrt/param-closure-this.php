<?php

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

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @param-closure-this T $cb
	 */
	public function paramClosureGenerics(string $class, callable $cb): void
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
		$this->paramClosureGenerics(\stdClass::class, function () {
			assertType(\stdClass::class, $this);
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

class ImplicitInheritance extends Foo
{

	public function paramClosureClass(callable $cb)
	{

	}

	public function paramClosureSelf(callable $cb)
	{

	}

	public function paramClosureStatic(callable $cb)
	{

	}

	public function paramClosureConditional(int $j, callable $ca)
	{
		// renamed parameter names
	}

	public function doFoo(): void
	{
		$this->paramClosureClass(function () {
			assertType(Some::class, $this);
		});
		$this->paramClosureClass(static function () {
			assertType('*ERROR*', $this);
		});
		$this->paramClosureSelf(function () use (&$a) {
			assertType(Foo::class, $this);
		});
		$this->paramClosureStatic(function () use (&$a) {
			assertType('static(ParamClosureThis\ImplicitInheritance)', $this);
		});
		$this->paramClosureConditional(1, function () {
			assertType(Foo::class, $this);
		});
		$this->paramClosureConditional(2, function () {
			assertType(Some::class, $this);
		});
		$this->paramClosureGenerics(\stdClass::class, function () {
			assertType(\stdClass::class, $this);
		});
	}

}

class ImplicitInheritanceMoreComplicated extends Foo
{

	/**
	 * @param callable $cb
	 */
	public function paramClosureClass(callable $cb)
	{

	}

	/**
	 * @param callable $cb
	 */
	public function paramClosureSelf(callable $cb)
	{

	}

	/**
	 * @param callable $cb
	 */
	public function paramClosureStatic(callable $cb)
	{

	}

	/**
	 * @param callable $ca
	 */
	public function paramClosureConditional(int $j, callable $ca)
	{
		// renamed parameter names
	}

	public function doFoo(): void
	{
		$this->paramClosureClass(function () {
			assertType(Some::class, $this);
		});
		$this->paramClosureClass(static function () {
			assertType('*ERROR*', $this);
		});
		$this->paramClosureSelf(function () use (&$a) {
			assertType(Foo::class, $this);
		});
		$this->paramClosureStatic(function () use (&$a) {
			assertType('static(ParamClosureThis\ImplicitInheritanceMoreComplicated)', $this);
		});
		$this->paramClosureConditional(1, function () {
			assertType(Foo::class, $this);
		});
		$this->paramClosureConditional(2, function () {
			assertType(Some::class, $this);
		});
		$this->paramClosureGenerics(\stdClass::class, function () {
			assertType(\stdClass::class, $this);
		});
	}

}

class FooParent
{

}

class FooChild extends FooParent
{

	private string $secret = 'secret';

}

class StaticReturnClosureThis
{

	/**
	 * @param-closure-this FooChild $cb
	 * @param-closure-scope FooChild $cb
	 */
	public function withFooChild(callable $cb): void
	{

	}

	/**
	 * @param-closure-this Foo $cb
	 * @param-closure-scope Foo $cb
	 */
	public function withFoo(callable $cb): void
	{

	}

}

class TestStaticResolutionInParamClosureThis
{

	public function testStaticReturnType(StaticReturnClosureThis $o): void
	{
		$o->withFoo(function (): static {
			assertType('ParamClosureThis\Foo', $this);
			return $this;
		});

		$o->withFoo(fn (): static => $this);
	}

	public function testSelfReturnType(StaticReturnClosureThis $o): void
	{
		$o->withFooChild(function (): self {
			assertType('ParamClosureThis\FooChild', $this);
			return $this;
		});
	}

	public function testParentReturnType(StaticReturnClosureThis $o): void
	{
		$o->withFooChild(function (): parent {
			assertType('ParamClosureThis\FooChild', $this);
			return $this;
		});
	}

	public function testNullableStaticReturnType(StaticReturnClosureThis $o): void
	{
		$o->withFoo(function (): ?static {
			assertType('ParamClosureThis\Foo', $this);
			return rand() ? $this : null;
		});
	}

}

class ClosureThisWithoutScope
{

	public const SERVICE_FOO = 'foo';

	/**
	 * @param-closure-this Some $cb
	 */
	public function paramClosureThisOnly(callable $cb): void
	{

	}

	/**
	 * @param-closure-scope Some $cb
	 */
	public function paramClosureScopeOnly(callable $cb): void
	{

	}

	/**
	 * @param-closure-this Some $cb
	 * @param-closure-scope Some $cb
	 */
	public function paramClosureThisAndScope(callable $cb): void
	{

	}

	public function testThisWithoutScope(): void
	{
		$this->paramClosureThisOnly(function () {
			assertType(Some::class, $this);
			assertType("'foo'", self::SERVICE_FOO);
		});

		$this->paramClosureThisOnly(fn () => assertType("'foo'", self::SERVICE_FOO));
	}

	public function testScopeWithoutThis(): void
	{
		$this->paramClosureScopeOnly(function () {
			assertType(sprintf('$this(%s)', self::class), $this);
		});
	}

	public function testThisAndScope(): void
	{
		$this->paramClosureThisAndScope(function () {
			assertType(Some::class, $this);
		});
	}

}

class BoundScopeClass
{
	public const X = 'bound';
}

class ClosureScopeOnly
{
	public const X = 'enclosing';

	/**
	 * @param-closure-scope BoundScopeClass $cb
	 */
	public function withScope(callable $cb): void
	{
	}

	public function test(): void
	{
		$this->withScope(function () {
			assertType(sprintf('$this(%s)', ClosureScopeOnly::class), $this);
		});
	}

}
