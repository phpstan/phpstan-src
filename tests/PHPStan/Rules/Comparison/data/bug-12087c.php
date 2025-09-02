<?php // lint >= 8.1

namespace Bug12087c;

enum Button: int
{
	case On = 1;

	case Off = 0;
}

function doFoo() {
	$value = 10;

	is_null($foo = $value = Button::tryFrom($value));
}

function doFoo2() {
	$value = 10;

	is_null($foo ??= Button::tryFrom($value));
}
