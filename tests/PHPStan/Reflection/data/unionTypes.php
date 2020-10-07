<?php // lint >= 8.0

namespace NativeUnionTypes;

use function PHPStan\Analyser\assertNativeType;
use function PHPStan\Analyser\assertType;

class Foo
{

	public int|bool $fooProp;

	public function doFoo(int|bool $foo): self|Bar
	{
		assertType('bool|int', $foo);
		assertType('bool|int', $this->fooProp);
		assertNativeType('bool|int', $foo);
	}

}

class Bar
{

}

function doFoo(int|bool $foo): Foo|Bar
{
	assertType('bool|int', $foo);
	assertNativeType('bool|int', $foo);
}

function (Foo $foo): void {
	assertType('bool|int', $foo->fooProp);
	assertType('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $foo->doFoo(1));
	assertType('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', doFoo(1));
};

function (): void {
	$f = function (int|bool $foo): Foo|Bar {
		assertType('bool|int', $foo);
	};

	assertType('NativeUnionTypes\\Bar|NativeUnionTypes\\Foo', $f(1));
};
