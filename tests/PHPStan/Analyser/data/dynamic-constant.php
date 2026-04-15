<?php

namespace DynamicConstants;

use function PHPStan\Testing\assertType;

define('GLOBAL_PURE_CONSTANT', 123);
define('GLOBAL_DYNAMIC_CONSTANT', false);
define('GLOBAL_DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES', null);

class DynamicConstantClass
{
	const DYNAMIC_CONSTANT_IN_CLASS = 'abcdef';
	const DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES_IN_CLASS = 'xyz';
	const PURE_CONSTANT_IN_CLASS = 'abc123def';

	/** @var string|null */
	const DYNAMIC_NULL_WITH_PHPDOC_CONSTANT = null;

	/** @var list<string> */
	const DYNAMIC_EMPTY_ARRAY_WITH_PHPDOC_CONSTANT = [];
}

class NoDynamicConstantClass
{
	// constant name is same as in DynamicConstantClass, just to test
	const DYNAMIC_CONSTANT_IN_CLASS = 'xyz';

	private function rip()
	{
		assertType('string', DynamicConstantClass::DYNAMIC_CONSTANT_IN_CLASS);
		assertType('string|null', DynamicConstantClass::DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES_IN_CLASS);
		assertType("'abc123def'", DynamicConstantClass::PURE_CONSTANT_IN_CLASS);
		assertType("'xyz'", NoDynamicConstantClass::DYNAMIC_CONSTANT_IN_CLASS);
		assertType('bool', GLOBAL_DYNAMIC_CONSTANT);
		assertType('123', GLOBAL_PURE_CONSTANT);
		assertType('string|null', GLOBAL_DYNAMIC_CONSTANT_WITH_EXPLICIT_TYPES);
		assertType('string|null', DynamicConstantClass::DYNAMIC_NULL_WITH_PHPDOC_CONSTANT);
		assertType('list<string>', DynamicConstantClass::DYNAMIC_EMPTY_ARRAY_WITH_PHPDOC_CONSTANT);
	}
}
