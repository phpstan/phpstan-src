<?php

namespace TaggedUnions;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param array{A: int}|array{A: string} $foo */
	public function doFoo(array $foo)
	{
		assertType('array{A: int}|array{A: string}', $foo);
		if (is_int($foo['A'])) {
			assertType("array{A: int}", $foo);
			$foo['B'] = 'yo';
			assertType("array{A: int, B: 'yo'}", $foo);
		} else {
			assertType('array{A: string}', $foo);
		}

		assertType("array{A: int, B: 'yo'}|array{A: string}", $foo);
	}

	/** @param array{A: int, B: 1}|array{A: string, B: 2} $foo */
	public function doFoo2(array $foo)
	{
		assertType('array{A: int, B: 1}|array{A: string, B: 2}', $foo);
		if (is_int($foo['A'])) {
			assertType("array{A: int, B: 1}", $foo);
		} else {
			assertType("array{A: string, B: 2}", $foo);
		}

		assertType('array{A: int, B: 1}|array{A: string, B: 2}', $foo);
	}

	/** @param array{A: int, B: 1}|array{A: string, C: 1} $foo */
	public function doFoo3(array $foo)
	{
		assertType('array{A: int, B: 1}|array{A: string, C: 1}', $foo);
		if (is_int($foo['A'])) {
			assertType("array{A: int, B: 1}", $foo);
		} else {
			assertType("array{A: string, C: 1}", $foo);
		}

		assertType('array{A: int, B: 1}|array{A: string, C: 1}', $foo);
	}

	/** @param array{A: int, B: 1}|array{A: string, C: 1} $foo */
	public function doFoo4(array $foo)
	{
		assertType('array{A: int, B: 1}|array{A: string, C: 1}', $foo);
		if (isset($foo['C'])) {
			assertType("array{A: string, C: 1}", $foo);
		} else {
			assertType("array{A: int, B: 1}", $foo);
		}

		assertType('array{A: int, B: 1}|array{A: string, C: 1}', $foo);
	}

	/**
	 * @param array{A: int}|array{A: int|string} $foo
	 * @return void
	 */
	public function doBar(array $foo)
	{
		assertType('array{A: int|string}', $foo);
	}

}

/**
 * @phpstan-type Topping string
 * @phpstan-type Salsa   string
 *
 * @phpstan-type Pizza   array{type: 'pizza', toppings: Topping[]}
 * @phpstan-type Pasta   array{type: 'pasta', salsa: Salsa}
 * @phpstan-type Meal    Pizza|Pasta
 */
class Test
{
	/**
	 * @param Meal $meal
	 */
	function test($meal): void {
		assertType("array{type: 'pasta', salsa: string}|array{type: 'pizza', toppings: array<string>}", $meal);
		if ($meal['type'] === 'pizza') {
			assertType("array{type: 'pizza', toppings: array<string>}", $meal);
		} else {
			assertType("array{type: 'pasta', salsa: string}", $meal);
		}
		assertType("array{type: 'pasta', salsa: string}|array{type: 'pizza', toppings: array<string>}", $meal);
	}
}

class HelloWorld
{
	/**
	 * @return array{updated: true, id: int}|array{updated: false, id: null}
	 */
	public function sayHello(): array
	{
		return ['updated' => false, 'id' => 5];
	}

	public function doFoo()
	{
		$x = $this->sayHello();
		assertType("array{updated: false, id: null}|array{updated: true, id: int}", $x);
		if ($x['updated']) {
			assertType('array{updated: true, id: int}', $x);
		}
	}
}

/**
 * @psalm-type A array{tag: 'A', foo: bool}
 * @psalm-type B array{tag: 'B'}
 */
class X {
	/** @psalm-param A|B $arr */
	public function ooo(array $arr): void {
		assertType("array{tag: 'A', foo: bool}|array{tag: 'B'}", $arr);
		if ($arr['tag'] === 'A') {
			assertType("array{tag: 'A', foo: bool}", $arr);
		} else {
			assertType("array{tag: 'B'}", $arr);
		}
		assertType("array{tag: 'A', foo: bool}|array{tag: 'B'}", $arr);
	}
}

class TipsFromArnaud
{

	// https://github.com/phpstan/phpstan/issues/7666#issuecomment-1191563801

	/**
	 * @param array{a: int}|array{a: int} $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array{a: int}', $a);
	}

	/**
	 * @param array{a: int}|array{a: string} $a
	 */
	public function doFoo2(array $a): void
	{
		// could be: array{a: int|string}
		assertType('array{a: int}|array{a: string}', $a);
	}

	/**
	 * @param array{a: int, b: int}|array{a: string, b: string} $a
	 */
	public function doFoo3(array $a): void
	{
		assertType('array{a: int, b: int}|array{a: string, b: string}', $a);
	}

	/**
	 * @param array{a: int, b: string}|array{a: string, b:string} $a
	 */
	public function doFoo4(array $a): void
	{
		assertType('array{a: int|string, b: string}', $a);
	}

	/**
	 * @param array{a: int, b: string, c: string}|array{a: string, b: string, c: string} $a
	 */
	public function doFoo5(array $a): void
	{
		assertType('array{a: int|string, b: string, c: string}', $a);
	}

}
