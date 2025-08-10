<?php

// test file for ExpressionTypeResolverExtensionTest

use function PHPStan\Testing\assertType;

global $MY_FRAMEWORK_GLOBAL, $ANOTHER_GLOBAL;

assertType('bool', $MY_FRAMEWORK_GLOBAL);
assertType('mixed', $ANOTHER_GLOBAL);
