<?php declare(strict_types = 1);

namespace Bug2861Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @param object|string $objectOrClass
 */
function testObjectOrString($objectOrClass): void {
	if (property_exists($objectOrClass, 'foo')) {
		assertType('(class-string&hasProperty(foo))|(object&hasProperty(foo))', $objectOrClass);
	}
}

/**
 * @param object|class-string $objectOrClass
 */
function testObjectOrClassString($objectOrClass): void {
	if (property_exists($objectOrClass, 'bar')) {
		assertType('(class-string&hasProperty(bar))|(object&hasProperty(bar))', $objectOrClass);
	}
}
