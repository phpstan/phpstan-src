<?php

namespace VariableCloning;

class Foo {};

$f = function () {
	$foo = new Foo();
	clone $foo;
	clone new Foo();
	clone (random_int(0, 1) ? 'loremipsum' : 123);

	$stringData = 'abc';
	clone $stringData;
	clone 'abc';

	/** @var Foo|string $bar */
	$bar = doBar();
	clone $bar;

	/** @var Foo|Bar|null $baz */
	$baz = doBaz();
	clone $baz;

	// Implicit mixed
	$lorem = doLorem();
	clone $lorem;

	/** @var object $object */
	$object = doFoo();
	clone $object;
};
