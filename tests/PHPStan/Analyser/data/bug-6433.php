<?php // lint >= 8.1

namespace Bug6433;

use Ds\Set;
use function PHPStan\Testing\assertType;

enum E: string {
	case A = 'A';
	case B = 'B';
}

class Foo
{

	function x(): void {
		assertType('Ds\Set<Bug6433\E>', new Set([E::A, E::B]));
	}

}

