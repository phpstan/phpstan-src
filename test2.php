<?php

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

/**
 * @param array{bar?: null}|array{bar?: 'hello'} $a
 */
 function optionalOffsetNull($a): void
{
	if (isset($a['bar'])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 'hello'}", $a);
		$a['bar'] = 1;
		assertType("array{bar: 1}", $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType('array{bar?: null}', $a);
	}

	assertVariableCertainty(TrinaryLogic::createYes(), $a);
	assertType("array{bar: 1}|array{bar?: null}", $a);
}
