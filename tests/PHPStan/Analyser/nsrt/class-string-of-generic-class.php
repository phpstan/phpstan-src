<?php // lint >= 8.0

namespace ClassStringOfGenericClass;

use function PHPStan\Testing\assertType;

/** @template-covariant T */
final class Box {}

/** @template-covariant T */
interface Option {}

/**
 * @template-covariant T
 * @implements Option<T>
 */
final class Some implements Option {}

/** @implements Option<string> */
final class StringOption implements Option {}

/** @param class-string<Box<string>> $c */
function finalClass(string $c): void
{
	if ($c === Box::class) {
		assertType("'ClassStringOfGenericClass\\\\Box'", $c);
		return;
	}

	assertType('*NEVER*', $c);
}

/** @param class-string<Option<int>> $c */
function subclass(string $c): void
{
	if ($c === Some::class) {
		assertType("'ClassStringOfGenericClass\\\\Some'", $c);
	}

	if ($c === StringOption::class) {
		assertType('*NEVER*', $c);
	}
}

/** @param class-string<Box<string>> $c */
function exhaustiveMatch(string $c): int
{
	return match ($c) {
		Box::class => 1,
	};
}
