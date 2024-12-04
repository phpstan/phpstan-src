<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug5101;

class FooBar
{
	public $x;
}

final class FinalFooBar
{
	public $x;
}

/** @param array<FooBar> $arrClass */
function doFoo(array $arrClass) {
	$arrFinalClass = [new FinalFooBar()];

	var_dump(array_column($arrClass, 'x'));
	var_dump(array_column($arrClass, 'y'));
	var_dump(array_column($arrFinalClass, 'x'));
	var_dump(array_column($arrFinalClass, 'y'));
}
