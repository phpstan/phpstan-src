<?php declare(strict_types = 1);

namespace Bug12447Bis;

use function PHPStan\Testing\assertType;

interface Foo
{

}

class HelloWorld
{

	public function __invoke(Foo $foo): void
	{
		$a = $foo->doFoo();
		assertType('*ERROR*', $a);
		$a[] = 5;
		assertType('mixed', $a);
	}
}
