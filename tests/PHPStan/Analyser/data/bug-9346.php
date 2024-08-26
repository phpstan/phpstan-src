<?php

namespace Bug9346;

use DateTime;

/**
 * @phpstan-type MyType array{
 *     key?: string,
 * }
 */
class Test
{
	/**
	 * @param MyType $params
	 */
	public function create(array $params = []): object
	{
		return new class($params) {
			/**
			 * @param MyType $params
			 */
			public function __construct(private array $params)
			{
			}
		};
	}
}

/**
 * @phpstan-type FooJson array{bar: string}
 */
interface Foo
{
	/**
	 * @phpstan-return FooJson
	 */
	public function sayHello(DateTime $date): array;
}

/**
 * @phpstan-import-type FooJson from Foo
 */
class HelloWorld
{
	public function createFoo(): Foo
	{
		$foo = new class() implements Foo {
			/**
			 * @phpstan-var FooJson $var
			 */
			private array $var = ['bar' => 'baz'];

			/**
			 * @phpstan-return FooJson
			 */
			public function sayHello(DateTime $date): array
			{
				return $this->var;
			}
		};
		return $foo;
	}
}
