<?php declare(strict_types = 1);

namespace ConditionalGuardConstantArrayMembers;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

function notProducedByOtherBranch(bool $c1, bool $c2): void
{
	if ($c2) {
		$list = $c1 ? ['z'] : ['a', 'b'];
	} else {
		$list = ['a'];
		$x = 1;
	}

	if ($list === ['z']) {
		assertVariableCertainty(TrinaryLogic::createNo(), $x);
	}
}

function maybeProducedByCallable(callable $t, bool $c): void
{
	if ($c) {
		$v = ['A', 'b'];
	} else {
		$v = $t;
		$x = 1;
	}

	// ['A', 'b'] may be the callable as well
	if ($v === ['A', 'b']) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $x);
	}
}

/**
 * @param mixed $m
 */
function nonArrayMember($m, bool $c): void
{
	if ($c) {
		$v = $m;
		$x = 1;
	} else {
		$v = 5;
	}

	if ($v !== 5) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
}

/**
 * @param array{a?: string, ...} $arr
 */
function unsealedShape(array $arr): void
{
	if (array_key_exists('a', $arr)) {
		$v = $arr;
		$x = 1;
	} else {
		$v = $arr;
	}

	if (array_key_exists('a', $v)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $x);
	}
}
