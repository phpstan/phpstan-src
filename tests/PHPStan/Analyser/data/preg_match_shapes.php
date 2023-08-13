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

function doOnlyNmedSubpattern(string $s): void {
	// n modifier captures only named groups
	if (preg_match('/(\w)-(?P<num>\d+)-(\w)/n', $s, $matches)) {
		assertType('array<string>', $matches); // could be "array{0: string, num?: string, 1?: string}"
	}
	assertType('array<string>', $matches);
}
