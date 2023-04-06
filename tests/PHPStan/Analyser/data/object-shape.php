<?php

namespace ObjectShape;

use function PHPStan\Testing\assertType;

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
