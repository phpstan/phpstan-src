<?php // onlyif PHP_VERSION_ID >= 80100

namespace NewInInitializers;

use function PHPStan\Testing\assertType;

assertType('stdClass', TEST_OBJECT_CONSTANT);
assertType('null', TEST_NULL_CONSTANT);
assertType('true', TEST_TRUE_CONSTANT);
assertType('false', TEST_FALSE_CONSTANT);
assertType('array{true, false, null}', TEST_ARRAY_CONSTANT);
assertType('EnumTypeAssertions\\Foo::ONE', TEST_ENUM_CONSTANT);
