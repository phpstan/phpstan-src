<?php // lint >= 8.0

declare(strict_types = 1);

namespace NamedArgumentRenamedParameter;

interface Foo
{

	public function doFoo(string $a): void;

}

class Bar implements Foo
{

	public function doFoo(string $b): void
	{

	}

}

function (Foo $foo): void {
	$foo->doFoo(a: 'a');
};
