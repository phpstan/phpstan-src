<?php // lint >= 8.0

namespace ClosureBindThisType;

use Closure;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

interface FooInterface
{

}

class Foo
{

	private int $secret = 1;

}

class SubFoo extends Foo
{

}

class NoParent
{

}

final class FinalNoParent
{

}

class Test
{

	/**
	 * @param class-string<Foo> $fooClass
	 */
	public function doFoo(
		object $object,
		Foo $foo,
		SubFoo $subFoo,
		NoParent $noParent,
		FinalNoParent $finalNoParent,
		?Foo $nullableFoo,
		Foo|NoParent $fooOrNoParent,
		string $fooClass,
		$mixed,
	): void
	{
		// no bound object: there is no $this, whatever the scope
		Closure::bind(function () {
			assertType('*ERROR*', $this);
			assertNativeType('*ERROR*', $this);
		}, null, Foo::class);

		// the scope class refines a bound object that might be an instance of it
		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo', $this);
			assertNativeType('object', $this);
		}, $object, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\SubFoo', $this);
			assertNativeType('ClosureBindThisType\SubFoo', $this);
		}, $subFoo, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo', $this);
			assertNativeType('ClosureBindThisType\Foo', $this);
		}, $foo, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\FooInterface&ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\NoParent', $this);
		}, $noParent, FooInterface::class);

		// a bound object that may be an instance of the scope class is assumed to be one
		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo', $this);
			assertNativeType('mixed', $this);
		}, $mixed, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\SubFoo', $this);
			assertNativeType('ClosureBindThisType\Foo', $this);
		}, $foo, SubFoo::class);

		// binding $this into another class's scope keeps the enclosing $this
		Closure::bind(function () {
			assertType('$this(ClosureBindThisType\Test)', $this);
			assertNativeType('$this(ClosureBindThisType\Test)', $this);
		}, $this, Foo::class);

		Closure::bind(function () {
			assertType('$this(ClosureBindThisType\Test)&ClosureBindThisType\FooInterface', $this);
			assertNativeType('$this(ClosureBindThisType\Test)', $this);
		}, $this, FooInterface::class);

		// ... but never replaces a bound object that cannot be one
		Closure::bind(function () {
			assertType('ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\NoParent', $this);
		}, $noParent, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\FinalNoParent', $this);
			assertNativeType('ClosureBindThisType\FinalNoParent', $this);
		}, $finalNoParent, FooInterface::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo|ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\Foo|ClosureBindThisType\NoParent', $this);
		}, $fooOrNoParent, Foo::class);

		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo|null', $this);
			assertNativeType('ClosureBindThisType\Foo|null', $this);
		}, $nullableFoo, Foo::class);

		// without a scope the bound object is $this as it is
		Closure::bind(function () {
			assertType('ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\NoParent', $this);
		}, $noParent);

		Closure::bind(function () {
			assertType('object', $this);
			assertNativeType('object', $this);
		}, $object);

		// the scope given as an object
		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo', $this);
			assertNativeType('object', $this);
		}, $object, $foo);

		Closure::bind(function () {
			assertType('ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\NoParent', $this);
		}, $noParent, $foo);

		// the scope given as a class-string
		Closure::bind(function () {
			assertType('ClosureBindThisType\Foo', $this);
			assertNativeType('object', $this);
		}, $object, $fooClass);

		Closure::bind(function () {
			assertType('ClosureBindThisType\NoParent', $this);
			assertNativeType('ClosureBindThisType\NoParent', $this);
		}, $noParent, $fooClass);

		// 'static' keeps the current scope, it names no class
		Closure::bind(function () {
			assertType('object', $this);
			assertNativeType('object', $this);
		}, $object, 'static');

		// arrow functions
		Closure::bind(fn () => assertType('ClosureBindThisType\NoParent', $this), $noParent, Foo::class);
		Closure::bind(fn () => assertType('ClosureBindThisType\Foo', $this), $object, Foo::class);
		Closure::bind(fn () => assertType('*ERROR*', $this), null, Foo::class);

		// a static closure has no $this to bind
		Closure::bind(static function () {
			assertType('*ERROR*', $this);
		}, $object, Foo::class);
		Closure::bind(static fn () => assertType('*ERROR*', $this), $object, Foo::class);
	}

}
