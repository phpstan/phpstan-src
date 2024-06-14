<?php declare(strict_types = 1);

namespace Bug7391;

use function PHPStan\Testing\assertType;

class Foo
{
	public const X = '5';
	public const Cl = self::class;
}

function () {
	$foo = Foo::Cl;
	assertType("'Bug7391\\\\Foo'", $foo);
	assertType("'5'", $foo::X);
	assertType('*ERROR*', $foo::class);
};
