<?php

namespace LowercaseString;

class Foo
{

	/** @param lowercase-string $s */
	function doFoo($s) {
		if ($s === 'AB') {}
		if ('AB' === $s) {}
		if ('AB' !== $s) {}
		if ($s === 'ab') {}
		if ($s !== 'ab') {}
		if ($s === 'aBc') {}
		if ($s !== 'aBc') {}
		if ($s === '01') {}
		if ($s === '1e2') {}
		if ($s === MyClass::myConst) {}
		if ($s === A_GLOBAL_CONST) {}
		if (doFoo('hi') === 'AB') {}
	}

	/** @param lowercase-string $s */
	function doFoo2($s, int $x, int $y) {
		$part = substr($s, $x, $y);

		if ($part === 'AB') {}
    }

}
