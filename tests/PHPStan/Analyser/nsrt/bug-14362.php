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

function () : void {
	assertType('Closure(Bug14362\B): int', C::u()(...));
	assertType('Closure(Bug14362\B): int', D::u()(...));
};
