<?php // lint >= 8.1

namespace Bug12087;

enum Button: int
{
	case On = 1;

	case Off = 0;
}

$value = 10;

is_null($value = Button::tryFrom($value));

