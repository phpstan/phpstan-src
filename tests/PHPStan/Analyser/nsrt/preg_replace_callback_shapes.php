<?php // lint >= 7.4

namespace PregReplaceCallbackMatchShapes;

use function PHPStan\Testing\assertType;

function (string $s): void {
	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		function ($matches) {
			assertType('array{string, non-empty-string|null, non-empty-string|null, non-empty-string|null}', $matches);
			return '';
		},
		$s,
		-1,
		$count,
		PREG_UNMATCHED_AS_NULL
	);
};

function (string $s): void {
	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		function ($matches) {
			assertType('array{0: array{string, int<0, max>}, 1?: array{non-empty-string, int<0, max>}, 2?: array{non-empty-string, int<0, max>}, 3?: array{non-empty-string, int<0, max>}}', $matches);
			return '';
		},
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE
	);
};

function (string $s): void {
	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		function ($matches) {
			assertType('array{array{string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}}', $matches);
			return '';
		},
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL
	);
};
