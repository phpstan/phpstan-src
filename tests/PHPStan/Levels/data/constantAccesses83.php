<?php // lint >= 8.3

namespace Levels\ConstantAccesses83;

class Foo
{

	public const FOO_CONSTANT = 'foo';

}

function (Foo $foo, string $a, int $i, int|string $is, ?string $sn): void {
	echo Foo::FOO_CONSTANT;

	echo Foo::{$a};
	echo $foo::{$a};

	echo Foo::{$i};
	echo Foo::{$is};
	echo Foo::{$sn};
};
