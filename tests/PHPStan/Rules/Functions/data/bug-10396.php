<?php // lint >= 7.4

namespace Bug10396;

/**
 * @param callable(array<int|string, array{string|null, int}>): string $callback
 */
function acceptCallback(callable $callback): void {}

// Reproduce the exact issue: user explicitly types callback for PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL
function testOffsetCaptureWithUnmatchedAsNull(string $s): ?string {
	return preg_replace_callback(
		'/(foo)/',
		/** @param array<int|string, array{string|null, int}> $matches */
		function (array $matches): string {
			return $matches[0][0] ?? '';
		},
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL
	);
}

// PREG_OFFSET_CAPTURE only
function testOffsetCapture(string $s): ?string {
	return preg_replace_callback(
		'/(foo)(bar)/',
		/** @param array<int|string, array{string, int}> $matches */
		function (array $matches): string {
			return $matches[0][0];
		},
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);
}

// PREG_UNMATCHED_AS_NULL only
function testUnmatchedAsNull(string $s): ?string {
	return preg_replace_callback(
		'/(foo)?(bar)/',
		/** @param array<int|string, string|null> $matches */
		function (array $matches): string {
			return $matches[0] ?? '';
		},
		$s,
		-1,
		$count,
		PREG_UNMATCHED_AS_NULL
	);
}

// No flags - existing behavior should still work
function testNoFlags(string $s): ?string {
	return preg_replace_callback(
		'/(foo)(bar)/',
		function (array $matches): string {
			return $matches[0];
		},
		$s
	);
}
