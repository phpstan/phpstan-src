<?php

namespace IssetCoalesceEmptyTypeRootScope;

use function PHPStan\Testing\assertType;

$alwaysDefinedNotNullable = 'string';
if (rand(0, 1)) {
	$sometimesDefinedVariable = 1;
}

assertType('true', isset($alwaysDefinedNotNullable));
assertType('bool', isset($sometimesDefinedVariable));
assertType('bool', isset($neverDefinedVariable));

assertType('mixed~null', $foo ?? false);

$bar = 'abc';
assertType('\'abc\'', $bar ?? false);
