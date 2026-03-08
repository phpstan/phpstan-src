<?php

use function PHPStan\Testing\assertType;

function () {
	if (!is_int($integer)) {
		throw new \Exception();
	}

	if (!is_integer($anotherInteger)) {
		throw new \Exception();
	}

	if (!is_long($longInteger)) {
		throw new \Exception();
	}

	if (!is_float($float)) {
		throw new \Exception();
	}

	if (!is_double($doubleFloat)) {
		throw new \Exception();
	}

	if (!is_real($realFloat)) {
		throw new \Exception();
	}

	if (!is_null($null)) {
		throw new \Exception();
	}

	if (!is_array($array)) {
		throw new \Exception();
	}

	if (!is_bool($bool)) {
		throw new \Exception();
	}

	if (!is_callable($callable)) {
		throw new \Exception();
	}

	if (!is_resource($resource)) {
		throw new \Exception();
	}

	if (!is_string($string)) {
		throw new \Exception();
	}

	if (!is_object($object)) {
		throw new \Exception();
	}

	if (!is_int($mixedInteger) && !ctype_digit($whatever)) {
		return;
	}

	/** @var int|\stdClass $intOrStdClass */
	$intOrStdClass = doFoo();
	if (!is_numeric($intOrStdClass)) {
		return;
	}

	$foo = doFoo();
	if (!is_a($foo, 'Foo')) {
		return;
	}

	$anotherFoo = doFoo();
	if (!is_a($anotherFoo, Foo::class)) {
		return;
	}

	assert(is_int($yetAnotherInteger));

	$subClassOfFoo = doFoo();
	if (!is_subclass_of($subClassOfFoo, Foo::class)) {
		return;
	}

	function (Foo $foo) {
		if (!is_subclass_of($foo, '')) {

		}
	};

	/** @var string $someClass */
	$someClass = doFoo();

	/** @var mixed $subClassOfFoo2 */
	$subClassOfFoo2 = doFoo();
	if (!is_subclass_of($subClassOfFoo2, Foo::class, false)) {
		return;
	}

	/** @var mixed $subClassOfFoo3 */
	$subClassOfFoo3 = doFoo();
	if (!is_subclass_of($subClassOfFoo3, $someClass)) {
		return;
	}

	/** @var mixed $subClassOfFoo4 */
	$subClassOfFoo4 = doFoo();
	if (!is_subclass_of($subClassOfFoo4, $someClass, false)) {
		return;
	}

	/** @var string|object $subClassOfFoo5 */
	$subClassOfFoo5 = doFoo();
	if (!is_subclass_of($subClassOfFoo5, Foo::class)) {
		return;
	}

	/** @var string|object $subClassOfFoo6 */
	$subClassOfFoo6 = doFoo();
	if (!is_subclass_of($subClassOfFoo6, $someClass)) {
		return;
	}

	/** @var string|object $subClassOfFoo7 */
	$subClassOfFoo7 = doFoo();
	if (!is_subclass_of($subClassOfFoo7, Foo::class, false)) {
		return;
	}

	/** @var string|object $subClassOfFoo8 */
	$subClassOfFoo8 = doFoo();
	if (!is_subclass_of($subClassOfFoo8, $someClass, false)) {
		return;
	}

	/** @var object $subClassOfFoo9 */
	$subClassOfFoo9 = doFoo();
	if (!is_subclass_of($subClassOfFoo9, $someClass, false)) {
		return;
	}

	/** @var object $subClassOfFoo10 */
	$subClassOfFoo10 = doFoo();
	if (!is_subclass_of($subClassOfFoo10, $someClass)) {
		return;
	}

	/** @var object $subClassOfFoo11 */
	$subClassOfFoo11 = doFoo();
	if (!is_subclass_of($subClassOfFoo11, Foo::class)) {
		return;
	}

	/** @var object $subClassOfFoo12 */
	$subClassOfFoo12 = doFoo();
	if (!is_subclass_of($subClassOfFoo12, Foo::class, false)) {
		return;
	}

	/** @var string|Foo|Bar|object $subClassOfFoo13 */
	$subClassOfFoo13 = doFoo();
	if (!is_subclass_of($subClassOfFoo13, Foo::class, false)) {
		return;
	}

	/** @var string|Foo|Bar|object $subClassOfFoo14 */
	$subClassOfFoo14 = doFoo();
	if (!is_subclass_of($subClassOfFoo14, $someClass, false)) {
		return;
	}

	/** @var string|Foo|Bar|object $subClassOfFoo15 */
	$subClassOfFoo15 = doFoo();
	if (!is_subclass_of($subClassOfFoo15, Foo::class)) {
		return;
	}

	/** @var string|Foo|Bar $subClassOfFoo16 */
	$subClassOfFoo16 = doFoo();
	if (!is_subclass_of($subClassOfFoo16, $someClass)) {
		return;
	}

	assertType('int', $integer);
	assertType('int', $anotherInteger);
	assertType('int', $longInteger);
	assertType('float', $float);
	assertType('float', $doubleFloat);
	assertType('float', $realFloat);
	assertType('null', $null);
	assertType('array<mixed, mixed>', $array);
	assertType('bool', $bool);
	assertType('callable(): mixed', $callable);
	assertType('resource', $resource);
	assertType('int', $yetAnotherInteger);
	assertType('*ERROR*', $mixedInteger);
	assertType('string', $string);
	assertType('object', $object);
	assertType('int', $intOrStdClass);
	assertType('Foo', $foo);
	assertType('Foo', $anotherFoo);
	assertType('class-string<Foo>|Foo', $subClassOfFoo);
	assertType('Foo', $subClassOfFoo2);
	assertType('class-string|object', $subClassOfFoo3);
	assertType('object', $subClassOfFoo4);
	assertType('class-string<Foo>|Foo', $subClassOfFoo5);
	assertType('class-string|object', $subClassOfFoo6);
	assertType('Foo', $subClassOfFoo7);
	assertType('object', $subClassOfFoo8);
	assertType('object', $subClassOfFoo9);
	assertType('object', $subClassOfFoo10);
	assertType('Foo', $subClassOfFoo11);
	assertType('Foo', $subClassOfFoo12);
	assertType('Foo', $subClassOfFoo13);
	assertType('object', $subClassOfFoo14);
	assertType('class-string<Foo>|Foo', $subClassOfFoo15);
	assertType('Bar|class-string|Foo', $subClassOfFoo16);
};
