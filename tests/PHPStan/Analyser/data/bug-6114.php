<?php // lint >= 8.0

namespace Bug6114;

/**
 * @template T
 */
interface Foo {
	/**
	 * @return T
	 */
	public function bar(): mixed;
}

class HelloWorld
{
	/**
	 * @template T
	 * @param T $value
	 * @return Foo<T>
	 */
	public function sayHello(mixed $value): Foo
	{
		return new
		/**
		 * @template U
		 * @implements Foo<U>
		 */ class($value) implements Foo {
			/** @var U */
			private mixed $value;

			/**
			 * @param U $value
			 */
			public function __construct(mixed $value) {
				$this->value = $value;
			}

			public function bar(): mixed
			{
				return $this->value;
			}
		};
	}
}
