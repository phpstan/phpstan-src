<?php

namespace Bug8249;

use function PHPStan\Testing\assertType;

function foo(): mixed
{
	return null;
}

class Foo
{
	public $x;
}

class Bar
{
	public static $x;
}

function () {
	$x = foo();

	if (is_int($x)) {
		assertType('int', $x);
		assertType('true', is_int($x));
	} else {
		assertType('mixed~int', $x);
		assertType('false', is_int($x));
	}
};

function () {
	$x = ['x' => foo()];

	if (is_int($x['x'])) {
		assertType('int', $x['x']);
		assertType('true', is_int($x['x']));
	} else {
		assertType('mixed~int', $x['x']);
		assertType('false', is_int($x['x']));
	}
};

function (Foo $x) {
	if (is_int($x->x)) {
		assertType('int', $x->x);
		assertType('true', is_int($x->x));
	} else {
		assertType('mixed~int', $x->x);
		assertType('false', is_int($x->x));
	}
};


function () {
	if (is_int(Bar::$x)) {
		assertType('int', Bar::$x);
		assertType('true', is_int(Bar::$x));
	} else {
		assertType('mixed~int', Bar::$x);
		assertType('false', is_int(Bar::$x));
	}
};
