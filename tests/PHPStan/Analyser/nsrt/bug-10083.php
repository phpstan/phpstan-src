<?php // lint >= 8.1

namespace Bug10083;

use function PHPStan\Testing\assertType;

enum Foo
{

	case Abc;
	case Bcd;

}

/**
 * @template TFoo of Foo::*
 * @param TFoo $foo
 */
function checkFoo($foo): void
{
}

/**
 * @template TFoo of Foo::*
 * @param TFoo $foo
 */
function narrowEnum($foo): void
{
	if (Foo::Abc === $foo) {
		assertType('TFoo of Bug10083\Foo::Abc (function Bug10083\narrowEnum(), argument)', $foo);
	} else {
		assertType('TFoo of Bug10083\Foo::Bcd (function Bug10083\narrowEnum(), argument)', $foo);
	}

	$filter = Foo::Abc === $foo ? 'Abc' : 'Bcd';
	assertType('TFoo of Bug10083\Foo::Abc (function Bug10083\narrowEnum(), argument)|TFoo of Bug10083\Foo::Bcd (function Bug10083\narrowEnum(), argument)', $foo);
	checkFoo($foo);
}

/**
 * @template TInt of int
 * @param TInt $int
 */
function narrowIntRange($int): void
{
	if ($int >= 0 && $int <= 5) {
		assertType('TInt of int<0, 5> (function Bug10083\narrowIntRange(), argument)', $int);
	}
}
