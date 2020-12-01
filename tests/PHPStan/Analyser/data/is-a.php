<?php

function (object $foo) {
	if (is_a($foo, Foo::class)) {
		\PHPStan\Analyser\assertType('Foo', $foo);
	}
};

function (object $foo) {
	/** @var class-string<Foo> $fooClassString */
	$fooClassString = 'Foo';

	if (is_a($foo, $fooClassString)) {
		\PHPStan\Analyser\assertType('Foo', $foo);
	}
};

function (string $foo) {
	if (is_a($foo, Foo::class, true)) {
		\PHPStan\Analyser\assertType('class-string<Foo>', $foo);
	}
};

function (string $foo, string $someString) {
	if (is_a($foo, $someString, true)) {
		\PHPStan\Analyser\assertType('class-string<object>', $foo);
	}
};
