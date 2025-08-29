<?php declare(strict_types = 1);

namespace Bug12989;

/**
 * @template T of int|null
 * @phpstan-param T $b
 * @phpstan-return int|T
 */
function a(?int $b): ?int
{
	if ($b === null) {
		return $b;
	}
	return $b;
}

