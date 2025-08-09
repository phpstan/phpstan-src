<?php declare(strict_types = 1);

namespace Bug12224Function;

/**
 * @phpstan-pure
 * @phpstan-assert string $value
 */
function string(mixed $value): string
{
	if (\is_string($value)) {
		return $value;
	}
	throw new \RuntimeException();
}

/** @var string|null $a */
$a = '';
string($a);
