<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14914;

function doFoo(): void
{
	preg_replace_callback(
		'/a|(?<b>b)/',
		function (array $match): string {
			if ($match['b'] !== null) {
				return 'aa';
			}
			return 'possible?';
		},
		'abcd',
		flags: PREG_UNMATCHED_AS_NULL,
	);
}

function doBar(): void
{
	preg_replace_callback(
		'/a|(?<b>b)/',
		fn (array $match) => $match['b'] !== null ? 'aa' : 'possible?',
		'abcd',
		flags: PREG_UNMATCHED_AS_NULL,
	);
}
