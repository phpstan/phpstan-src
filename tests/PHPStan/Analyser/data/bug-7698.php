<?php

namespace Bug7698;

declare(strict_types=1);

use function PHPStan\Testing\assertType;

final class A
{
}

final class B
{
}

final class Test
{
	public function __construct(public readonly A|B $value)
	{
	}
}

function foo()
{
	$t = new Test(new A());
	$class = $t->value::class;
	assertType("'Bug7698\\\\A'|'Bug7698\\\\B'", $class);

	if ($class === A::class) {
		return;
	}

	assertType("'Bug7698\\\\B'", $class);

	if ($class === B::class) {
		return;
	}

	assertType('*NEVER*', $class);
}
