<?php // lint >= 8.0

declare(strict_types = 1);

namespace MatchGenericSealedClassString;

/**
 * @template-covariant T
 * @phpstan-sealed BarCov|BazCov
 */
abstract class FooCov {}

/** @template-covariant T @extends FooCov<T> */
final class BarCov extends FooCov {}

/** @template-covariant T @extends FooCov<T> */
final class BazCov extends FooCov {}

/** @param FooCov<string> $foo */
function testTemplateCovariant(FooCov $foo): string {
	return match ($foo::class) {
		BarCov::class => 'bar',
		BazCov::class => 'baz',
	};
}

/**
 * @template T
 * @phpstan-sealed BarInv|BazInv
 */
abstract class FooInv {}

/** @template T @extends FooInv<T> */
final class BarInv extends FooInv {}

/** @template T @extends FooInv<T> */
final class BazInv extends FooInv {}

/** @param FooInv<covariant string> $foo */
function testCovariantParam(FooInv $foo): string {
	return match ($foo::class) {
		BarInv::class => 'bar',
		BazInv::class => 'baz',
	};
}
