<?php // lint >= 8.1

namespace Bug12087c;

enum Button: int
{
	case On = 1;

	case Off = 0;
}

function doFoo()
{
	$foo = 'abc';
	$value = 10;

	is_null($value = $foo = Button::tryFrom($value));
}

function doFoo2() {
	$value = 10;

	is_null($value ??= Button::tryFrom($value));
}

function doFoo3() {
	$value = null;

	is_null($value ??= Button::tryFrom($value));
}

