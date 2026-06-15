<?php

namespace Bug12179;

function alwaysDefinedNullableParam(?string $name): ?string
{
	// $name is always defined but nullable - the `?? null` is unnecessary
	return $name ?? null;
}

function maybeUndefinedVariable(): ?string
{
	if (rand() > 0.5) {
		$x = 'foo';
	}

	// $x might be undefined - not reported
	return $x ?? null;
}

/** @param array{foo?: ?int, bar: ?int} $x */
function foo(array $x): void {
	var_dump($x['foo'] ?? null); // Fine
	var_dump($x['bar'] ?? null); // ?? null is redundant here
}
