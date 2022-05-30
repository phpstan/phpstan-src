<?php declare(strict_types = 1);

namespace Bug7351;

function value(): string
{
	return 'v';
}

function test(): void
{
	$keys = [
		value(),
		value(),
	];

	while (array_pop($keys) !== null) {
		// do something
	}
}
