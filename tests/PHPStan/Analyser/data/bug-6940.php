<?php declare(strict_types = 1);

namespace Bug6940;

use function PHPStan\Testing\assertType;

class Bug6940
{

	public function foo(): void
	{
		$b = [] == [];
		assertType('bool', $b);
	}

}
