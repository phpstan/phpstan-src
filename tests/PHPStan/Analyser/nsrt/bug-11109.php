<?php declare(strict_types = 1);

namespace Bug11109;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function (): void {
	$bool = 1 == random_int(1, 2)
		&& 2 == random_int(1, 2)
		&& in_array(
			'foo',
			[$var = 'foo', 'bar']
		);

	if ($bool) {
		// here $bool is true
		// so $var must be defined
		// because the 3rd condition above must have been fulfilled for the $bool to be true
		// so 2nd argument to in_array() must have been computed
		assertVariableCertainty(TrinaryLogic::createYes(), $var);
		assertType("'foo'", $var);
		throw new \Exception($var);
	}

	assertVariableCertainty(TrinaryLogic::createMaybe(), $var);
};

function (): void {
	$bool = 1 == random_int(1, 2)
		|| in_array(random_int(1, 2), [$var = 1, 3], true);

	if (!$bool) {
		assertVariableCertainty(TrinaryLogic::createYes(), $var);
		assertType('1', $var);
	} else {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $var);
	}
};
