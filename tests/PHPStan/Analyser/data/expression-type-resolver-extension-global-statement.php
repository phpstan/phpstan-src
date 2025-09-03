<?php

// test file for ExpressionTypeResolverExtensionTest

use function PHPStan\Testing\assertType;

global $MY_GLOBAL_BOOL, $ANOTHER_GLOBAL;

assertType('bool', $MY_GLOBAL_BOOL);
assertType('mixed', $MY_GLOBAL_INT); // not declared in the global statement = no type assigned
assertType('mixed', $ANOTHER_GLOBAL);

$testFct = function ($MY_GLOBAL_BOOL) {
	/** @var float $MY_GLOBAL_STR */
	global $MY_GLOBAL_INT, $MY_GLOBAL_STR, $MY_GLOBAL_ARRAY;

	$MY_GLOBAL_ARRAY = new ArrayIterator([1, 2, 3]);

	assertType('mixed', $MY_GLOBAL_BOOL); // not declared in the global statement = no type assigned
	assertType('float', $MY_GLOBAL_STR); // overriden by PHPDoc
	assertType('ArrayIterator<int, int>', $MY_GLOBAL_ARRAY); // overriden by value assign expression
	assertType('int', $MY_GLOBAL_INT);
};

$testClass = new class () {
	public function foo($MY_GLOBAL_INT) {
		global $MY_GLOBAL_STR;

		assertType('string', $MY_GLOBAL_STR);
		assertType('mixed', $MY_GLOBAL_INT);
	}
};
