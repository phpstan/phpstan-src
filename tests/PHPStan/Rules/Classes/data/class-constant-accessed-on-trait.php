<?php // lint >= 8.2

namespace ClassConstantAccessedOnTrait;

trait Foo
{
	public const TEST = 1;
}

class Bar
{
	use Foo;
}

function (): void {
	echo Foo::TEST;
	echo Foo::class;
};
