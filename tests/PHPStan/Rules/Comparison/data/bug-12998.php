<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug12998;

final class A {}
final class B {}

class C {}
class D {}

/**
 * Final classes with template: match IS exhaustive
 *
 * @template T of A|B
 *
 * @param class-string<T> $class
 */
function fooFinal(string $class): string
{
	return match ($class) {
		A::class => 'a',
		B::class => 'b',
	};
}

/**
 * Non-final classes with template: match can't be exhaustive
 *
 * @template T of C|D
 *
 * @param class-string<T> $class
 */
function fooNonFinal(string $class): string
{
	return match ($class) {
		C::class => 'c',
		D::class => 'd',
	};
}
