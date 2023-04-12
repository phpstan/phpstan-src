<?php

namespace ObjectShape;

use function method_exists;
use function PHPStan\Testing\assertType;
use function property_exists;

class Foo
{

	/**
	 * @param object{foo: self, bar: int, baz?: string} $o
	 */
	public function doFoo($o): void
	{
		assertType('object{foo: ObjectShape\Foo, bar: int, baz?: string}', $o);
		assertType(self::class, $o->foo);
		assertType('int', $o->bar);
		assertType('*ERROR*', $o->baz);
	}

	/**
	 * @param object{foo: self, bar: int, baz?: string} $o
	 */
	public function doFoo2(object $o): void
	{
		assertType('object{foo: ObjectShape\Foo, bar: int, baz?: string}', $o);
	}

	public function doBaz(): void
	{
		assertType('object{}&stdClass', (object) []);

		$a = ['bar' => 2];
		if (rand(0, 1)) {
			$a['foo'] = 1;
		}

		assertType('object{bar: 2, foo?: 1}&stdClass', (object) $a);
	}

	/**
	 * @template T
	 * @param object{foo: int, bar: T} $o
	 * @return T
	 */
	public function generics(object $o)
	{

	}

	public function testGenerics()
	{
		$o = (object) ['foo' => 1, 'bar' => new \Exception()];
		assertType('object{foo: 1, bar: Exception}&stdClass', $o);
		assertType('1', $o->foo);
		assertType('Exception', $o->bar);

		assertType('Exception', $this->generics($o));
	}

	/**
	 * @return object{foo: static}
	 */
	public function returnObjectShapeWithStatic(): object
	{

	}

	public function testObjectShapeWithStatic()
	{
		assertType('object{foo: static(ObjectShape\Foo)}', $this->returnObjectShapeWithStatic());
	}

}

class FooChild extends Foo
{

}

class Bar
{

	public function doFoo(Foo $foo)
	{
		assertType('object{foo: ObjectShape\Foo}', $foo->returnObjectShapeWithStatic());
	}

	public function doFoo2(FooChild $foo)
	{
		assertType('object{foo: ObjectShape\FooChild}', $foo->returnObjectShapeWithStatic());
	}

}

class OptionalProperty
{

	/**
	 * @param object{foo: string, bar?: int} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{
		assertType('object{foo: string, bar?: int}', $o);
		if (isset($o->foo)) {
			assertType('object{foo: string, bar?: int}', $o);
		}

		assertType('object{foo: string, bar?: int}', $o);
		if (isset($o->bar)) {
			assertType('object{foo: string, bar: int}', $o);
		}

		assertType('object{foo: string, bar?: int}', $o);
	}

	/**
	 * @param object{foo: string, bar?: int} $o
	 * @return void
	 */
	public function doBar(object $o): void
	{
		assertType('object{foo: string, bar?: int}', $o);
		if (property_exists($o, 'foo')) {
			assertType('object{foo: string, bar?: int}', $o);
		}

		assertType('object{foo: string, bar?: int}', $o);
		if (property_exists($o, 'bar')) {
			assertType('object{foo: string, bar: int}', $o);
		}

		assertType('object{foo: string, bar?: int}', $o);
	}

}

class MethodExistsCheck
{

	/**
	 * @param object{foo: string, bar?: int} $o
	 */
	public function doFoo(object $o): void
	{
		if (method_exists($o, 'doFoo')) {
			assertType('object{foo: string, bar?: int}&hasMethod(doFoo)', $o);
		} else {
			assertType('object{foo: string, bar?: int}', $o);
		}

		assertType('object{foo: string, bar?: int}', $o);
	}

}

class ObjectWithProperty
{

	public function doFoo(object $o): void
	{
		if (property_exists($o, 'foo')) {
			assertType('object&hasProperty(foo)', $o);
		} else {
			assertType('object', $o);
		}
		assertType('object', $o);

		if (isset($o->foo)) {
			assertType('object&hasProperty(foo)', $o);
		} else {
			assertType('object', $o);
		}
		assertType('object', $o);
	}

}

class TestTemplate
{

	/**
	 * @template T of object{foo: int}
	 * @param T $o
	 * @return T
	 */
	public function doBar(object $o): object
	{
		return $o;
	}

	/**
	 * @param object{foo: positive-int} $o
	 * @return void
	 */
	public function doFoo(object $o): void
	{
		assertType('object{foo: int<1, max>}', $this->doBar($o));
	}

}
