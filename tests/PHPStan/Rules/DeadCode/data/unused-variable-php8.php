<?php // lint >= 8.0

namespace UnusedVariableRulePhp8;

/** @param mixed $v */
function sink($v): void
{
}

/**
 * @return mixed
 * @phpstan-impure
 */
function source()
{
	return rand();
}

function catchUnused(): void
{
	try {
		sink(1);
	} catch (\Exception $e) { // unused $e
	}
}

function catchUsed(): void
{
	try {
		sink(1);
	} catch (\Exception $e) {
		sink($e);
	}
}

function matchRead(int $i): int
{
	$a = source();
	return match ($i) {
		1 => $a,
		default => 0,
	};
}

function nullsafeRead(?\stdClass $o): void
{
	$x = $o?->foo;
	sink($x);
}

function namedArgs(): void
{
	$v = 1;
	sink(v: $v);
}
