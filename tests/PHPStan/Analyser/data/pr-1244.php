<?php

namespace Pr1244;

use function PHPStan\Testing\assertType;

function foo() {
	/** @var string $string */
	$string = doFoo();

	assertType('string|null', var_export());
	assertType('null', var_export($string));
	assertType('null', var_export($string, false));
	assertType('string', var_export($string, true));

	assertType('bool|string', highlight_string());
	assertType('bool', highlight_string($string));
	assertType('bool', highlight_string($string, false));
	assertType('string', highlight_string($string, true));

	assertType('bool|string', highlight_file());
	assertType('bool', highlight_file($string));
	assertType('bool', highlight_file($string, false));
	assertType('string', highlight_file($string, true));

	assertType('bool|string', show_source());
	assertType('bool', show_source($string));
	assertType('bool', show_source($string, false));
	assertType('string', show_source($string, true));

	assertType('string|true', print_r());
	assertType('true', print_r($string));
	assertType('true', print_r($string, false));
	assertType('string', print_r($string, true));
}
