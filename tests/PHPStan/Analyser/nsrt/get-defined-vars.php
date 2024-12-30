<?php

namespace GetDefinedVars;

use function PHPStan\Testing\assertType;

$global = "foo";

assertType('array<string, mixed>', get_defined_vars()); // any variable can exist

function doFoo(int $param) {
	$local = "foo";
	assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\'}', get_defined_vars());
	assertType('array{\'GLOBALS\', \'_SERVER\', \'_GET\', \'_POST\', \'_FILES\', \'_COOKIE\', \'_SESSION\', \'_REQUEST\', \'_ENV\', \'param\', \'local\'}', array_keys(get_defined_vars()));
}

function doBar(int $param) {
	global $global;
	$local = "foo";
	assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, global: mixed, local: \'foo\'}', get_defined_vars());
	assertType('array{\'GLOBALS\', \'_SERVER\', \'_GET\', \'_POST\', \'_FILES\', \'_COOKIE\', \'_SESSION\', \'_REQUEST\', \'_ENV\', \'param\', \'global\', \'local\'}', array_keys(get_defined_vars()));
}

function doConditional(int $param) {
	$local = "foo";
	if(true) {
		$conditional = "bar";
		assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', conditional: \'bar\'}', get_defined_vars());
	} else {
		$other = "baz";
		assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', other: \'baz\'}', get_defined_vars());
	}
	assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', conditional: \'bar\'}', get_defined_vars());
}

function doRandom(int $param) {
	$local = "foo";
	if(rand(0, 1)) {
		$random1 = "bar";
		assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', random1: \'bar\'}', get_defined_vars());
	} else {
		$random2 = "baz";
		assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', random2: \'baz\'}', get_defined_vars());
	}
	assertType('array{GLOBALS: array<mixed>, _SERVER: array<mixed>, _GET: array<mixed>, _POST: array<mixed>, _FILES: array<mixed>, _COOKIE: array<mixed>, _SESSION: array<mixed>, _REQUEST: array<mixed>, _ENV: array<mixed>, param: int, local: \'foo\', random2?: \'baz\', random1?: \'bar\'}', get_defined_vars());
}
