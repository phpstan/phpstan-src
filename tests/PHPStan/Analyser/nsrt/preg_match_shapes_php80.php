<?php // lint >= 8.0

namespace PregMatchShapesPhp82;

use function PHPStan\Testing\assertType;

function doOffsetCaptureWithUnmatchedNull(string $s): void {
	// see https://3v4l.org/07rBO#v8.2.9
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL)) {
		assertType('array{array{string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}}', $matches);
	}
	assertType('array{}|array{array{string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}, array{non-empty-string|null, int<-1, max>}}', $matches);
}

function doNonAutoCapturingModifier(string $s): void {
	if (preg_match('/(?n)(\d+)/', $s, $matches)) {
		// should be assertType('array{string}', $matches);
		assertType('array{string, numeric-string}', $matches);
	}
	assertType('array{}|array{string, numeric-string}', $matches);
}
