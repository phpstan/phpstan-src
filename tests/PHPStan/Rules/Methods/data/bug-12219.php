<?php // lint >= 8.1

namespace Bug12219;

class Bug
{
	/**
	 * @template T of \BackedEnum
	 * @param class-string<T> $class
	 * @param value-of<T> $value
	 */
	public function foo(string $class, mixed $value): void
	{
	}

	/**
	 * @template Q of \BackedEnum
	 * @param class-string<Q> $class
	 * @param value-of<Q> $value
	 */
	public function bar(string $class, mixed $value): void
	{
		// Parameter #2 $value of static method Bug::foo() contains unresolvable type.
		$this->foo($class, $value);
	}
}
