<?php

namespace ConditionalExprNarrowingThroughVariable;

use function PHPStan\Testing\assertType;

class Holder
{

	/**
	 * @return array{int, string}|null
	 * @phpstan-pure
	 */
	public function getPair(): ?array
	{
		throw new \Exception();
	}

}

function pureMethodCallNarrowsThroughVariable(Holder $a, Holder $b): void
{
	$bothReady = $a->getPair() !== null && $b->getPair() !== null;

	if ($bothReady) {
		$aPair = $a->getPair();
		$bPair = $b->getPair();

		assertType('array{int, string}', $aPair);
		assertType('array{int, string}', $bPair);

		assertType('int', $aPair[0]);
		assertType('int', $bPair[0]);
	}
}

function pregMatchNarrowsByRefVariable(string $in): void
{
	$matches = [];
	$result = preg_match('~^/xxx/([\w\-]+)/?([\w\-]+)?/?$~', $in, $matches);
	if ($result) {
		// preg_match has impure points (it writes $matches by ref), but $matches
		// is a plain variable so the narrowing attached to it must still survive
		// through the stored `$result` guard.
		assertType('array{0: non-falsy-string, 1: non-empty-string, 2?: non-empty-string}', $matches);
	}
}
