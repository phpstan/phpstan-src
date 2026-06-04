<?php

namespace CallToFunctionWithoutImpurePointsThrows;

class InvalidValue extends \Exception
{

}

/**
 * @param array<int> $ints
 * @throws InvalidValue
 */
function throwingFunc(array $ints)
{
	foreach ($ints as $int) {
		if (!is_int($int)) {
			throw new InvalidValue();
		}
	}
}

function noThrowsFunc()
{
}

function (): void {
	throwingFunc([1, 2, 3]);
	noThrowsFunc();
};
