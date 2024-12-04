<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12188;

enum Foo
{
	case A;
	case B;
}

function doFoo() {
	$arr = [Foo::A, Foo::B];

	var_dump(array_column($arr, 'value'));
}
