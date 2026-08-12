<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14914Arrow;

function doFoo(): void
{
	preg_replace_callback(
		'/a|(?<b>b)/',
		fn (array $match) => $match['b'] !== null ? 'aa' : 'possible?',
		'abcd',
		flags: PREG_UNMATCHED_AS_NULL,
	);
}
