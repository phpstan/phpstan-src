<?php

namespace Bug4875;

use function PHPStan\Testing\assertType;

interface Blah
{
}

interface Mock
{
}

class Foo
{

	/**
	 * @template T of object
	 * @param class-string<T> $interface
	 * @return T&Mock
	 */
	function mockIt(string $interface): object
	{
		return eval("new class implements $interface, Mock {}");
	}

	function doFoo()
	{
		$mock = $this->mockIt(Blah::class);

		assertType('Bug4875\Blah&Bug4875\Mock', $mock);
		assertType('class-string<Bug4875\Blah&Bug4875\Mock>&literal-string', $mock::class);
		assertType('class-string<Bug4875\Blah&Bug4875\Mock>', get_class($mock));
	}

}
