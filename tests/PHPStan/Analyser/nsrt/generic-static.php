<?php

namespace GenericStatic;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U
 */
interface Foo
{

	/**
	 * @template V
	 * @param callable(): V $cb
	 * @return static<T, V>
	 */
	public function map(callable $cb);

	/** @return static<U, T> */
	public function flip();

	/** @return static<T, U> */
	public function fluent();

	/** @return static<T, static<U>> */
	public function nested();

}

/**
 * @template T
 * @template U
 * @implements Foo<T, U>
 */
class FooImpl implements Foo
{

	public function map(callable $cb)
	{

	}

	public function flip()
	{

	}

	public function fluent()
	{

	}

	public function doFoo(): void
	{
		assertType('static(GenericStatic\FooImpl<T (class GenericStatic\FooImpl, argument), 1>)', $this->map(function () {
			return 1;
		}));

		assertType('static(GenericStatic\FooImpl<U (class GenericStatic\FooImpl, argument), T (class GenericStatic\FooImpl, argument)>)', $this->flip());
		assertType('static(GenericStatic\FooImpl<T (class GenericStatic\FooImpl, argument), U (class GenericStatic\FooImpl, argument)>)', $this->fluent());
		assertType('static(GenericStatic\FooImpl<T (class GenericStatic\FooImpl, argument), static(GenericStatic\FooImpl<U (class GenericStatic\FooImpl, argument), mixed>)>)', $this->nested());
	}

	/**
	 * @param FooImpl<string, float> $s
	 */
	public function doBar(self $s): void
	{
		assertType('GenericStatic\\FooImpl<string, 1>', $s->map(function () {
			return 1;
		}));

		assertType('GenericStatic\\FooImpl<float, string>', $s->flip());
		assertType('GenericStatic\\FooImpl<string, float>', $s->fluent());
		assertType('GenericStatic\FooImpl<string, GenericStatic\FooImpl<float, mixed>>', $s->nested());
	}

}

/**
 * @template T
 * @template U
 * @implements Foo<U, T>
 */
abstract class Inconsistent implements Foo
{

	public function fluent()
	{

	}

	/**
	 * @param Inconsistent<int, string> $s
	 */
	public function test(self $s): void
	{
		assertType('static(GenericStatic\Inconsistent<T (class GenericStatic\Inconsistent, argument), U (class GenericStatic\Inconsistent, argument)>)', $this->fluent());
		assertType('GenericStatic\\Inconsistent<int, string>', $s->fluent());
	}

}

/**
 * @template T
 * @implements Foo<float, T>
 */
abstract class Inconsistent2 implements Foo
{

	public function fluent()
	{

	}

	/**
	 * @param Inconsistent2<int> $s
	 */
	public function test(self $s): void
	{
		assertType('static(GenericStatic\Inconsistent2<T (class GenericStatic\Inconsistent2, argument)>)', $this->fluent());
		assertType('GenericStatic\\Inconsistent2<int>', $s->fluent());
	}

}

/**
 * @template T
 * @implements Foo<T, float>
 */
abstract class Inconsistent3 implements Foo
{

	public function fluent()
	{

	}

	/**
	 * @param Inconsistent3<int> $s
	 */
	public function test(self $s): void
	{
		assertType('static(GenericStatic\Inconsistent3<T (class GenericStatic\Inconsistent3, argument)>)', $this->fluent());
		assertType('GenericStatic\\Inconsistent3<int>', $s->fluent());
	}

}

/**
 * @template T
 * @template K
 */
class A {
	/** @return static<T, K> */
	public function doFoo() {}

}

/** @extends A<int, string> */
class B extends A {
	public function doBar(): void
	{
		$f = $this->doFoo();
		assertType('static(GenericStatic\B)', $f);
	}
}

function (): void {
	assertType(B::class, (new B)->doFoo());
};
