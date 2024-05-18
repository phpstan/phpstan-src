<?php

namespace PregMatchShapes;

use function PHPStan\Testing\assertType;


function doMatch(string $s): void {
	if (preg_match('/Price: (£|€)\d+/', $s, $matches)) {
		assertType('array{string, string}', $matches);
	} else {
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('/Price: (£|€)(\d+)/i', $s, $matches)) {
		assertType('array{string, string, string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('  /Price: (£|€)\d+/ i u', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('(Price: (£|€))i', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('_foo(.)\_i_i', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('/(a)(b)*(c)(d)*/', $s, $matches)) {
		assertType('array{0: string, 1: string, 2: string, 3?: string, 4?: string}', $matches);
	}
	assertType('array<string>', $matches);
}

function doNonCapturingGroup(string $s): void {
	if (preg_match('/Price: (?:£|€)(\d+)/', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array<string>', $matches);
}

function doNamedSubpattern(string $s): void {
	if (preg_match('/\w-(?P<num>\d+)-(\w)/', $s, $matches)) {
		// could be assertType('array{0: string, num: string, 1: string, 2: string, 3: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}

function doOffsetCapture(string $s): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE)) {
		assertType('array{array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}}', $matches);
	}
	assertType('array<array{string, int<-1, max>}>', $matches);
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, 1?: string|null, 2?: string|null, 3?: string|null}', $matches);
	}
	assertType('array<string|null>', $matches);
}

function doOffsetCaptureWithUnmatchedNull(string $s): void {
	// see https://3v4l.org/07rBO#v8.2.9
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL)) {
		assertType('array{array{null, -1}|array{string, int<0, max>}, array{null, -1}|array{string, int<0, max>}, array{null, -1}|array{string, int<0, max>}, array{null, -1}|array{string, int<0, max>}}', $matches);
	}
	assertType('array<array{null, -1}|array{string, int<0, max>}>', $matches);
}

function doUnknownFlags(string $s, int $flags): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, $flags)) {
		assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
	}
	assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
}

function doNonAutoCapturingModifier(string $s): void {
	if (preg_match('/(?n)(\d+)/', $s, $matches)) {
		// could be assertType('array{string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}

function doMultipleAlternativeCaptureGroupsWithSameNameWithModifier(string $s): void {
	if (preg_match('/(?J)(?<Foo>[a-z]+)|(?<Foo>[0-9]+)/', $s, $matches)) {
		// could be assertType('array{0: string, Foo: string, 1: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}

function doMultipleConsecutiveCaptureGroupsWithSameNameWithModifier(string $s): void {
	if (preg_match('/(?J)(?<Foo>[a-z]+)|(?<Foo>[0-9]+)/', $s, $matches)) {
		// could be assertType('array{0: string, Foo: string, 1: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);
}
