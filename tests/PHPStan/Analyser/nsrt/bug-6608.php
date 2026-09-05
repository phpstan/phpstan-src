<?php declare(strict_types = 1);

namespace Bug6608;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function (string $s): void {
	try {
		$var = new \DateTime($s);
	} catch (\Throwable $e) {}

	if (isset($e) || $var instanceof \DateTime) {
	}

	if (!isset($e)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $var);
		assertType('DateTime', $var);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $var);
	}
};
