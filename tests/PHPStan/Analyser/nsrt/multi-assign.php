<?php

namespace MultiAssign;

use function PHPStan\Testing\assertType;

function (): void {
	$foo = $bar = $baz = null;
	assertType('null', $foo);
	assertType('null', $bar);
	assertType('null', $baz);
	if (!$foo) {
		assertType('null', $foo);
		assertType('null', $bar);
		assertType('null', $baz);
	}

	if (!$bar) {
		assertType('null', $foo);
		assertType('null', $bar);
		assertType('null', $baz);
	}
};

function (bool $b): void {
	$foo = $bar = $baz = $b;
	if ($b) {
		assertType('true', $b);
		assertType('bool', $foo);
		assertType('bool', $bar);
		assertType('bool', $baz);
	} else {
		assertType('false', $b);
		assertType('bool', $foo);
		assertType('bool', $bar);
		assertType('bool', $baz);
	}
};

function (bool $b): void {
	$foo = $bar = $baz = $b;
	if ($foo) {
		assertType('true', $b);
		assertType('true', $foo);
		assertType('bool', $bar);
		assertType('bool', $baz);
	} else {
		assertType('false', $b);
		assertType('false', $foo);
		assertType('bool', $bar);
		assertType('bool', $baz);
	}
};

function (bool $b): void {
	$foo = $bar = $baz = $b;
	if ($bar) {
		assertType('bool', $b);
		assertType('bool', $foo);
		assertType('true', $bar);
		assertType('bool', $baz);
	} else {
		assertType('bool', $b);
		assertType('bool', $foo);
		assertType('false', $bar);
		assertType('bool', $baz);
	}
};

function (bool $b): void {
	$foo = $bar = $baz = $b;
	if ($baz) {
		assertType('bool', $b);
		assertType('bool', $foo);
		assertType('bool', $bar);
		assertType('true', $baz);
	} else {
		assertType('bool', $b);
		assertType('bool', $foo);
		assertType('bool', $bar);
		assertType('false', $baz);
	}
};
