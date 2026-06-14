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
