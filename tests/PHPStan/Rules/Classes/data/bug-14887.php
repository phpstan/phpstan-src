<?php declare(strict_types = 1);

namespace Bug14887;

/**
 * @template T
 * @param class-string<T> $interface
 * @return non-empty-list<T>
 */
function initClassesWithInterface(string $interface): array
{
	$classes = [];
	foreach (get_declared_classes() as $class) {
		if (
			is_subclass_of($class, $interface) &&
			! (new \ReflectionClass($class))->isAbstract()
		) {
			$classes[] = new $class;
		}
	}

	return $classes;
}
