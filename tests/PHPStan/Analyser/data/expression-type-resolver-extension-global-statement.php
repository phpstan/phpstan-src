<?php

// test file for ExpressionTypeResolverExtensionTest

use function PHPStan\Testing\assertType;

global $MY_GLOBAL_BOOL, $ANOTHER_GLOBAL;

assertType('bool', $MY_GLOBAL_BOOL);
assertType('mixed', $MY_GLOBAL_INT); // not declared in the global statement = no type assigned
assertType('mixed', $ANOTHER_GLOBAL);

$testFct = function ($MY_GLOBAL_BOOL) {
	global $MY_GLOBAL_INT;

	assertType('mixed', $MY_GLOBAL_BOOL);
	assertType('int', $MY_GLOBAL_INT);
};

$testClass = new class () {
	public function foo($MY_GLOBAL_INT) {
		global $MY_GLOBAL_STR;

		assertType('string', $MY_GLOBAL_STR);
		assertType('mixed', $MY_GLOBAL_INT);
	}
};
