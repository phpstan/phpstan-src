<?php // lint >= 8.1

namespace NewInInitializers;

use function PHPStan\Testing\assertType;

assertType('stdClass', TEST_OBJECT_CONSTANT);
