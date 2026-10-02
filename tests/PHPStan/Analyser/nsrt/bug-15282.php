<?php declare(strict_types = 1);

namespace Bug15282;

use function PHPStan\Testing\assertType;

/**
 * @return int<1, max>
 */
function getPositiveNumber(): int
{
	/** @var non-negative-int */
	$zeroOrMore = 0;
	/** @var positive-int */
	$oneOrMore = 1;

	assertType('int<0, max>', intdiv($zeroOrMore, $oneOrMore));
	assertType('int<1, max>', intdiv($zeroOrMore, $oneOrMore) + 1);

	return intdiv($zeroOrMore, $oneOrMore) + 1;
}
