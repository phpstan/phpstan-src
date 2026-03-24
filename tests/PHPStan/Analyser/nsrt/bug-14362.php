<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14362;

use function PHPStan\Testing\assertType;

interface A
{
	public function __invoke(B $b): int;
}

interface B
{

}

class C {
	public static function u(): A&B {
		return new class() implements A, B {
			public function __invoke(B $b): int {
				return 1;
			}
		};
	}
}

class D {
	public static function u(): A {
		return new class() implements A {
			public function __invoke(B $b): int {
				return 1;
			}
		};
	}
}

interface E
{
	public function __invoke(string $s): bool;
}

interface F
{

}

function () : void {
	assertType('Closure(Bug14362\B): int', C::u()(...));
	assertType('Closure(Bug14362\B): int', D::u()(...));

	// Intersection with only yes-callable types (both have __invoke)
	/** @var A&E $yesCallable */
	$yesCallable = C::u();
	assertType('(Closure(Bug14362\B): int)|(Closure(string): bool)', $yesCallable(...));

	// Intersection with only maybe-callable types (neither has __invoke)
	/** @var B&F $maybeCallable */
	$maybeCallable = C::u();
	assertType('Closure', $maybeCallable(...));
};
