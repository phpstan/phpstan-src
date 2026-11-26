<?php declare(strict_types = 1);

namespace PureRandom;

/** @phpstan-pure */
function seededRandom(): int
{
	return mt_rand();
}

/** @phpstan-pure */
function unseededRandom(): float
{
	return lcg_value();
}
