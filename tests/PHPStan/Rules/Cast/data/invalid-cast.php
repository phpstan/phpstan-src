<?php

function (
	string $str
) {
	(string) $str;
	(string) new \stdClass(); // error: Cannot cast stdClass to string.
	(string) new \Test\ClassWithToString();

	(object) new \stdClass();

	(float) 1.2;
	(int) $str; // ok
	(float) $str; // ok

	(int) [];

	(int) true; // ok
	(float) true; // ok
	(int) "123"; // ok
	(int) "blabla";

	(int) new \stdClass(); // error: Cannot cast stdClass to int.
	(float) new \stdClass(); // error: Cannot cast stdClass to float.

	(string) fopen('php://memory', 'r');
	(int) fopen('php://memory', 'r');
};

function (
	\Test\Foo $foo
) {
	/** @var object $object */
	$object = doFoo();
	(string) $object; // error: Cannot cast object to string.

	if (method_exists($object, '__toString')) {
		(string) $object;
	}

	(string) $foo; // error: Cannot cast Test\Foo to string.
	if (method_exists($foo, '__toString')) {
		(string) $foo;
	}

	/** @var array|float|int $arrayOrFloatOrInt */
	$arrayOrFloatOrInt = doFoo();
	(string) $arrayOrFloatOrInt; // error: Cannot cast array|float|int to string.
};

function (
	\SimpleXMLElement $xml
)
{
	(float) $xml;
	(int) $xml;
	(string) $xml;
	(bool) $xml;
};

function (): void {
	$ch = curl_init();
	(int) $ch;
};

function (): void {
	$ch = curl_multi_init();
	(int) $ch;
};
