<?php declare(strict_types = 1);

namespace Bug2861Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @param object|string $objectOrClass
 */
function testObjectOrString($objectOrClass): void {
	if (property_exists($objectOrClass, 'foo')) {
		assertType('object|string', $objectOrClass);
	}
}

/**
 * @param object|class-string $objectOrClass
 */
function testObjectOrClassString($objectOrClass): void {
	if (property_exists($objectOrClass, 'bar')) {
		assertType('class-string|object', $objectOrClass);
	}
}
