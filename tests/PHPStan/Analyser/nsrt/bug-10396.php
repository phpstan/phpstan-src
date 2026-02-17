<?php // lint >= 7.4

namespace Bug10396;

use function PHPStan\Testing\assertType;

// preg_replace_callback_array - without flags
function testCallbackArrayNoFlags(string $s): void {
	preg_replace_callback_array(
		[
			'/(foo)(bar)/' => function ($matches) {
				assertType("mixed", $matches); // no flags, no attribute set
				return '';
			},
		],
		$s
	);
}

// preg_replace_callback_array with PREG_UNMATCHED_AS_NULL
function testCallbackArrayUnmatchedAsNull(string $s): void {
	preg_replace_callback_array(
		[
			'/(foo)?(bar)/' => function ($matches) {
				assertType("array<int|string, string|null>", $matches);
				return '';
			},
		],
		$s,
		-1,
		$count,
		PREG_UNMATCHED_AS_NULL
	);
}

// preg_replace_callback_array with PREG_OFFSET_CAPTURE
function testCallbackArrayOffsetCapture(string $s): void {
	preg_replace_callback_array(
		[
			'/(foo)(bar)/' => function ($matches) {
				assertType("array<int|string, array{string, int<-1, max>}>", $matches);
				return '';
			},
		],
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);
}

// preg_replace_callback_array with both flags
function testCallbackArrayBothFlags(string $s): void {
	preg_replace_callback_array(
		[
			'/(foo)?(bar)/' => function ($matches) {
				assertType("array<int|string, array{string|null, int<-1, max>}>", $matches);
				return '';
			},
		],
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL
	);
}

// preg_replace_callback_array with arrow function
function testCallbackArrayArrowFunction(string $s): void {
	preg_replace_callback_array(
		[
			'/(foo)(bar)/' => fn ($matches) => assertType("array<int|string, array{string, int<-1, max>}>", $matches) ? '' : '',
		],
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);
}
