<?php

use function PHPStan\Testing\assertType;

const GLOBAL_CONST_DYNAMIC = false;
const GLOBAL_CONST_DYNAMIC_EXPLICIT = null;
const GLOBAL_CONST_PURE = 123;

assertType('bool', GLOBAL_CONST_DYNAMIC);
assertType('string|null', GLOBAL_CONST_DYNAMIC_EXPLICIT);
assertType('123', GLOBAL_CONST_PURE);
