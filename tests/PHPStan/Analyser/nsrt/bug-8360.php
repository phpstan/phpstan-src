<?php declare(strict_types = 1);

namespace Bug8360Nsrt;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function logicalOr(bool $cond, bool $f): void
{
	if ($cond || $f) {
		$x = 1;
	}

	if ($cond && $f) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
		echo $x;
	}
}
