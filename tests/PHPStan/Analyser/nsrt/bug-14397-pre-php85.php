<?php // lint < 8.5

declare(strict_types = 1);

namespace Bug14397PrePhp85;

use PHPStan\TrinaryLogic;

use function filter_var;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(mixed $mixed): void
{
	try {
		filter_var($mixed, FILTER_VALIDATE_INT, FILTER_FLAG_GLOBAL_RANGE);
		$foo = 1;
	} catch (\Filter\FilterFailedException $e) {
		assertVariableCertainty(TrinaryLogic::createYes(), $foo);
	}

	assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_FLAG_GLOBAL_RANGE));
	assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FLAG_GLOBAL_RANGE]));
}
