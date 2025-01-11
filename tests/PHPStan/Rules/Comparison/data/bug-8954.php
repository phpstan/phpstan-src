<?php declare(strict_types = 1);

namespace Bug8954;

/**
 * @template U
 * @template V
 *
 * @param ?class-string<U> $class
 * @param class-string<V> $expected
 *
 * @return ?class-string<V>
 */
function ensureSubclassOf(?string $class, string $expected): ?string {
	if ($class === null) {
		return $class;
	}

	if (!class_exists($class)) {
		throw new \Exception("Class “{$class}” does not exist.");
	}

	if (!is_subclass_of($class, $expected)) {
		throw new \Exception("Class “{$class}” is not a subclass of “{$expected}”.");
	}

	return $class;
}
