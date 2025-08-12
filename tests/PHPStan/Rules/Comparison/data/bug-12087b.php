<?php // lint >= 8.1

namespace Bug12087b;

enum Button: int
{
	case On = 1;

	case Off = 0;
}

class MyAssert {
	/**
	 * @return ($value is null ? true : false)
	 */
	static public function static_is_null(mixed $value): bool {
		return $value === null;
	}

	/**
	 * @return ($value is null ? true : false)
	 */
	public function is_null(mixed $value): bool {
		return $value === null;
	}
}

function doFoo(): void {
	$value = 10;

	MyAssert::static_is_null($value = Button::tryFrom($value));
}

function doBar(MyAssert $assert): void {
	$value = 10;

	$assert->is_null($value = Button::tryFrom($value));
}
