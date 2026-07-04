<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14904;

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
