<?php

declare(strict_types=1);

namespace ParseStr;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function doFoo(string $s): void
{
	parse_str($s);

	assertVariableCertainty(TrinaryLogic::createNo(), $a);
}

