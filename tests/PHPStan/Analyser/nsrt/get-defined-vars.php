<?php

namespace GetDefinedVars;

use function PHPStan\Testing\assertType;

$global = "foo";

assertType('array<string, mixed>', get_defined_vars()); // any variable can exist

function doFoo(int $param) {
	$local = "foo";
	assertType('array{param: int, local: \'foo\'}', get_defined_vars());
	assertType('array{\'param\', \'local\'}', array_keys(get_defined_vars()));
}

function doBar(int $param) {
	global $global;
	$local = "foo";
	assertType('array{param: int, global: mixed, local: \'foo\'}', get_defined_vars());
	assertType('array{\'param\', \'global\', \'local\'}', array_keys(get_defined_vars()));
}

function doConditional(int $param) {
	$local = "foo";
	if(true) {
		$conditional = "bar";
		assertType('array{param: int, local: \'foo\', conditional: \'bar\'}', get_defined_vars());
	} else {
		$other = "baz";
		assertType('array{param: int, local: \'foo\', other: \'baz\'}', get_defined_vars());
	}
	assertType('array{param: int, local: \'foo\', conditional: \'bar\'}', get_defined_vars());
}

function doRandom(int $param) {
	$local = "foo";
	if(rand(0, 1)) {
		$random1 = "bar";
		assertType('array{param: int, local: \'foo\', random1: \'bar\'}', get_defined_vars());
	} else {
		$random2 = "baz";
		assertType('array{param: int, local: \'foo\', random2: \'baz\'}', get_defined_vars());
	}
	assertType('array{param: int, local: \'foo\', random2?: \'baz\', random1?: \'bar\'}', get_defined_vars());
}
