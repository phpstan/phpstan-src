<?php // lint >= 8.1

namespace Bug8983;

use function PHPStan\Testing\assertType;

enum Enum1: string
{

	case FOO = 'foo';

}

enum Enum2: string
{

	case BAR = 'bar';

}

class Foo
{

	/** @param value-of<Enum1|Enum2> $bar */
	public function doFoo($bar): void
	{
		assertType("'bar'|'foo'", $bar);
	}

}
