<?php // lint >= 8.4

declare(strict_types = 1);

namespace SideEffectsAdvancingHandlesPhp84;

use function PHPStan\Testing\assertType;

class Foo
{

	public function nextCallReadsTheNextItem(): void
	{
		if (pcntl_waitid(P_ALL) === true) {
			assertType('bool', pcntl_waitid(P_ALL));
		}
	}

}
