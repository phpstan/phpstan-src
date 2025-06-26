<?php declare(strict_types = 1);

namespace Bug8818;

class MyEnum {
	public const ONE = 'one';
	public const TWO = 'two';
	public const THREE = 'three';
}

class HelloWorld
{
	/**
	 * @param list<MyEnum::*> $stack
	 */
	public function sayHello(array $stack): bool
	{
		return count($stack) === 1 && in_array(MyEnum::ONE, $stack, true);
	}
}
