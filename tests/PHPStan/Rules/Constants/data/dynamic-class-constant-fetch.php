<?php // lint >= 8.3

namespace DynamicClassConstantFetch;

class Foo
{

	public const FOO = 1;

}

function (Foo $foo, string $a, int $i, int|string $is, ?string $sn): void {
	echo Foo::FOO;

	echo Foo::{$a};
	echo $foo::{$a};

	echo Foo::{$i};
	echo Foo::{$is};
	echo Foo::{$sn};
};
