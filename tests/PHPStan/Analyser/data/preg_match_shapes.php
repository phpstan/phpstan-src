<?php

namespace PregMatchShapes;

use function PHPStan\Testing\assertType;

function doMatch(string $s): void {
	if (preg_match('/Price: (£|€)\d+/', $s, $matches)) {
		assertType('array{0: string, 1?: string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('/Price: (£|€)(\d+)/i', $s, $matches)) {
		assertType('array{0: string, 1?: string, 2?: string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('/(a)(b)*(c)(d)*/', $s, $matches)) {
		assertType('array{0: string, 1?: string, 2?: string, 3?: string, 4?: string}', $matches);
	}
	assertType('array<string>', $matches);
}

function doNonCapturingGroup(string $s): void {
	if (preg_match('/Price: (?:£|€)(\d+)/', $s, $matches)) {
		assertType('array{0: string, 1?: string}', $matches);
	}
	assertType('array<string>', $matches);
}

function doNamedSubpattern(string $s): void {
	if (preg_match('/\w-(?P<num>\d+)-(\w)/', $s, $matches)) {
		assertType('array{0: string, num?: string, 1?: string, 2?: string}', $matches);
	}
	assertType('array<string>', $matches);
}

function doOffsetCapture(string $s): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE)) {
		assertType('array{0: array{string, int<0, max>}, 1?: array{string, int<0, max>}, 2?: array{string, int<0, max>}, 3?: array{string, int<0, max>}}', $matches);
	}
	assertType('array<array{string, int<-1, max>}>', $matches);
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string|null, 1?: string|null, 2?: string|null, 3?: string|null}', $matches);
	}
	assertType('array<string|null>', $matches);
}

function doOffsetCaptureWithUnmatchedNull(string $s): void {
	// see https://3v4l.org/07rBO#v8.2.9
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: array{null, -1}|array{string, int<0, max>}, 1?: array{null, -1}|array{string, int<0, max>}, 2?: array{null, -1}|array{string, int<0, max>}, 3?: array{null, -1}|array{string, int<0, max>}}', $matches);
	}
	assertType('array<array{null, -1}|array{string, int<0, max>}>', $matches);
}

function doUnknownFlags(string $s, int $flags): void {
	if (preg_match('/(foo)(bar)(baz)/', 'foobarbaz', $matches, $flags)) {
		assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
	}
	assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
}
