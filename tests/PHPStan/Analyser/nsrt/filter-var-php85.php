<?php // lint >= 8.5

declare(strict_types=1);

namespace FilterVarPHP85;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class FilterVarPHP85
{

	public function doFoo($mixed): void
	{
		try {
			filter_var($mixed, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
			$foo = 1;
		} catch (\Filter\FilterFailedException $e) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}

		assertType('int', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
		assertType('int', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_THROW_ON_FAILURE]));
	}
}
