<?php declare(strict_types = 1);

namespace Bug8846;

use Exception;

enum Foo
{
	case ONE;
	case TWO;
	case THREE;
}

/**
 * @param Foo $foo
 * @return Foo::TWO|Foo::THREE
 */
function test(Foo $foo): Foo
{
	if ($foo === Foo::ONE) {
		throw new Exception();
	}
	return $foo;
}
