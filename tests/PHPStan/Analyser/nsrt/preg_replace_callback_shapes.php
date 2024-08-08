<?php // lint >= 7.4

namespace PregReplaceCallbackMatchShapes;

use function PHPStan\Testing\assertType;

function (string $s): void {
	preg_replace_callback(
		'/(foo)?(bar)?(baz)?/',
		function ($matches) {
			assertType("array{string, 'foo'|null, 'bar'|null, 'baz'|null}", $matches);
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
			assertType("array{0: array{string, int<0, max>}, 1?: array{''|'foo', int<0, max>}, 2?: array{''|'bar', int<0, max>}, 3?: array{'baz', int<0, max>}}", $matches);
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
			assertType("array{array{string|null, int<-1, max>}, array{'foo'|null, int<-1, max>}, array{'bar'|null, int<-1, max>}, array{'baz'|null, int<-1, max>}}", $matches);
			return '';
		},
		$s,
		-1,
		$count,
		PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL
	);
};
