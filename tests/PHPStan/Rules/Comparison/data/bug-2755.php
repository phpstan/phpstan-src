<?php declare(strict_types = 1);

namespace Bug2755;

/**
 * @param array<class-string<object>> $interfaces
 * @param array<class-string<object>> $classes
 */
function foo(array $interfaces, array $classes): void
{
	foreach ($interfaces as $interface) {
		foreach ($classes as $class) {
			if (is_subclass_of($class, $interface)) {

			}
		}
	}
}
