<?php

namespace CallUserFuncArray;

use function PHPStan\Analyser\assertType;


@assertType('mixed', call_user_func_array());

$foo = function () {};
assertType('mixed', call_user_func_array($foo, []));
$fooReturnsBool = function (): bool { return TRUE; };
assertType('bool', call_user_func_array($fooReturnsBool, []));

function foo() {}
assertType('mixed', call_user_func_array('\\CallUserFuncArray\\foo', []));
function fooReturnsBool(): bool { return TRUE; }
assertType('mixed', call_user_func_array('\\CallUserFuncArray\\fooReturnsBool', [])); // bool, not supported

/**
 * @return bool
 */
function fooReturnsBoolInPhpDoc() { return TRUE; }
assertType('mixed', call_user_func_array('\\CallUserFuncArray\\fooReturnsBoolInPhpDoc', [])); // bool, not supported

/**
 * @var callable(): bool $returnsBoolInVarPhpDoc
 */
$returnsBoolInVarPhpDoc = function () {};
assertType('bool', call_user_func_array($returnsBoolInVarPhpDoc, []));

/**
 * @return bool
 */
$returnsBoolInReturnPhpDoc = function () { return TRUE; };
assertType('mixed', call_user_func_array($returnsBoolInReturnPhpDoc, [])); // bool, not supported

class Foo {

	function returnsBool(): bool { return TRUE; }

	/**
	 * @return bool
	 */
	function returnsBoolInReturnPhpDoc() { return TRUE; }

}

$foo = new Foo();
assertType('bool', call_user_func_array([$foo, 'returnsBool'], []));
assertType('bool', call_user_func_array([$foo, 'returnsBoolInReturnPhpDoc'], []));

/**
 * @template T
 * @param T $a
 * @return T
 */
function returnsGeneric($a) { return $a; }
assertType('bool', call_user_func_array('\\CallUserFuncArray\\returnsGeneric', [TRUE]));
assertType('string', call_user_func_array('\\CallUserFuncArray\\returnsGeneric', ['a']));

class FooGeneric {

	/**
	 * @template T
	 * @param T $a
	 * @return T
	 */
	function returnsGeneric($a) { return $a; }

}

$fooGeneric = new FooGeneric();
assertType('bool', call_user_func_array([$fooGeneric, 'returnsGeneric'], [TRUE]));
assertType('string', call_user_func_array([$fooGeneric, 'returnsGeneric'], ['a']));
