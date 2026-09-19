<?php

namespace NonEmptyMixedParameter;

/** @param non-empty-mixed $value */
function acceptsNonEmptyMixed($value): void
{
}

/** @param mixed $value */
function acceptsPlainMixed($value): void
{
}

function doFoo(string $string, int $int, bool $bool): void
{
	acceptsNonEmptyMixed('');
	acceptsNonEmptyMixed('0');
	acceptsNonEmptyMixed(0);
	acceptsNonEmptyMixed(0.0);
	acceptsNonEmptyMixed([]);
	acceptsNonEmptyMixed(false);
	acceptsNonEmptyMixed(null);

	acceptsNonEmptyMixed('x');
	acceptsNonEmptyMixed(1);
	acceptsNonEmptyMixed(true);
	acceptsNonEmptyMixed([1]);
	acceptsNonEmptyMixed($string);
	acceptsNonEmptyMixed($int);
	acceptsNonEmptyMixed($bool);

	acceptsPlainMixed('');
	acceptsPlainMixed(null);
}

/** @param mixed $value */
function forwardsSubtractedMixed($value): void
{
	if ($value === null) {
		return;
	}

	acceptsPlainMixed($value);
}
