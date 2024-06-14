<?php

namespace Bug1283;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function (array $levels): void {
	foreach ($levels as $level) {
		switch ($level) {
			case 'all':
				continue 2;
			case 'some':
				$allowedElements = array(1, 3);
				break;
			case 'one':
				$allowedElements = array(1);
				break;
			default:
				throw new \UnexpectedValueException(sprintf('Unsupported level `%s`', $level));
		}

		assertType('array{0: 1, 1?: 3}', $allowedElements);
		assertVariableCertainty(TrinaryLogic::createYes(), $allowedElements);
	}
};
