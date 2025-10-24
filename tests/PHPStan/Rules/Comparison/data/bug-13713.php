<?php

declare(strict_types = 1);

namespace Bug13713;

function debug(object $object): void {
	if ($object instanceof \stdClass) {
		echo var_export(\is_subclass_of($object, \stdClass::class, false), true) . \PHP_EOL;
	}
	if ($object instanceof test) {
		echo var_export(\is_subclass_of($object, \stdClass::class, false), true) . \PHP_EOL;
	}
}

class test extends \stdClass {}
debug(new test);

/**
 * @param class-string<\stdClass> $stdClass
 * @param class-string<test> 	  $test
 */
function debugWithClass(string $stdClass, string $test): void {
	echo var_export(\is_subclass_of($stdClass, \stdClass::class, true), true) . \PHP_EOL;
	echo var_export(\is_subclass_of($test, \stdClass::class, true), true) . \PHP_EOL;
}

debugWithClass(test::class, test::class);
