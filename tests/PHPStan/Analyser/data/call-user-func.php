<?php

namespace CallUserFunc;

use function PHPStan\Analyser\assertType;


@assertType('mixed', call_user_func());

$foo = function () {};
assertType('mixed', call_user_func($foo));
$fooReturnsBool = function (): bool { return TRUE; };
assertType('bool', call_user_func($fooReturnsBool));

function foo() {}
assertType('mixed', call_user_func('\\CallUserFunc\\foo'));
function fooReturnsBool(): bool { return TRUE; }
assertType('mixed', call_user_func('\\CallUserFunc\\fooReturnsBool')); // bool, not supported

/**
 * @return bool
 */
function fooReturnsBoolInPhpDoc() { return TRUE; }
assertType('mixed', call_user_func('\\CallUserFunc\\fooReturnsBoolInPhpDoc')); // bool, not supported

/**
 * @var callable(): bool $returnsBoolInVarPhpDoc
 */
$returnsBoolInVarPhpDoc = function () {};
assertType('bool', call_user_func($returnsBoolInVarPhpDoc));

/**
 * @return bool
 */
$returnsBoolInReturnPhpDoc = function () { return TRUE; };
assertType('mixed', call_user_func($returnsBoolInReturnPhpDoc)); // bool, not supported

class Foo {

	function returnsBool(): bool { return TRUE; }

	/**
	 * @return bool
	 */
	function returnsBoolInReturnPhpDoc() { return TRUE; }

}

$foo = new Foo();
assertType('bool', call_user_func([$foo, 'returnsBool']));
assertType('bool', call_user_func([$foo, 'returnsBoolInReturnPhpDoc']));

/**
 * @template T
 * @param T $a
 * @return T
 */
function returnsGeneric($a) { return $a; }
assertType('bool', call_user_func('\\CallUserFunc\\returnsGeneric', TRUE));
assertType('string', call_user_func('\\CallUserFunc\\returnsGeneric', 'a'));

class FooGeneric {

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	function returnsGeneric($a) { return $a; }

}

$fooGeneric = new FooGeneric();
assertType('bool', call_user_func([$fooGeneric, 'returnsGeneric'], TRUE));
assertType('string', call_user_func([$fooGeneric, 'returnsGeneric'], 'a'));
