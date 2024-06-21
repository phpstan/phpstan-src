<?php // lint >= 7.4

namespace PregMatchShapes;

use function PHPStan\Testing\assertType;


function doMatch(string $s): void {
	if (preg_match('/Price: /i', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array{}|array{string}', $matches);

	if (preg_match('/Price: (£|€)\d+/', $s, $matches)) {
		assertType('array{string, string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('/Price: (£|€)(\d+)/i', $s, $matches)) {
		assertType('array{string, string, string}', $matches);
	}
	assertType('array{}|array{string, string, string}', $matches);

	if (preg_match('  /Price: (£|€)\d+/ i u', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('(Price: (£|€))i', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('_foo(.)\_i_i', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('/(a)(b)*(c)(d)*/', $s, $matches)) {
		assertType('array{0: string, 1: string, 2: string, 3?: string, 4?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1: string, 2: string, 3?: string, 4?: string}', $matches);

	if (preg_match('/(a|b)|(?:c)/', $s, $matches)) {
		assertType('array{0: string, 1?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1?: string}', $matches);

	if (preg_match('/(foo)(bar)(baz)+/', $s, $matches)) {
		assertType('array{string, string, string, string}', $matches);
	}
	assertType('array{}|array{string, string, string, string}', $matches);

	if (preg_match('/(foo)(bar)(baz)*/', $s, $matches)) {
		assertType('array{0: string, 1: string, 2: string, 3?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1: string, 2: string, 3?: string}', $matches);

	if (preg_match('/(foo)(bar)(baz)?/', $s, $matches)) {
		assertType('array{0: string, 1: string, 2: string, 3?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1: string, 2: string, 3?: string}', $matches);

	if (preg_match('/(foo)(bar)(baz){0,3}/', $s, $matches)) {
		assertType('array{0: string, 1: string, 2: string, 3?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1: string, 2: string, 3?: string}', $matches);

	if (preg_match('/(foo)(bar)(baz){2,3}/', $s, $matches)) {
		assertType('array{string, string, string, string}', $matches);
	}
	assertType('array{}|array{string, string, string, string}', $matches);
}

function doNonCapturingGroup(string $s): void {
	if (preg_match('/Price: (?:£|€)(\d+)/', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);
}

function doNamedSubpattern(string $s): void {
	if (preg_match('/\w-(?P<num>\d+)-(\w)/', $s, $matches)) {
		// could be assertType('array{0: string, num: string, 1: string, 2: string, 3: string}', $matches);
		assertType('array<string>', $matches);
	}
	assertType('array<string>', $matches);

	if (preg_match('/^(?<name>\S+::\S+)/', $s, $matches)) {
		assertType('array{0: string, name: string, 1: string}', $matches);
	}
	assertType('array{}|array{0: string, name: string, 1: string}', $matches);

	if (preg_match('/^(?<name>\S+::\S+)(?:(?<dataname> with data set (?:#\d+|"[^"]+"))\s\()?/', $s, $matches)) {
		assertType('array{0: string, name: string, 1: string, dataname?: string, 2?: string}', $matches);
	}
	assertType('array{}|array{0: string, name: string, 1: string, dataname?: string, 2?: string}', $matches);
}

function doOffsetCapture(string $s): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE)) {
		assertType('array{array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}}', $matches);
	}
	assertType('array{}|array{array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}, array{string, int<0, max>}}', $matches);
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, 1?: string|null, 2?: string|null, 3?: string|null}', $matches);
	}
	assertType('array{}|array{0: string, 1?: string|null, 2?: string|null, 3?: string|null}', $matches);
}

function doUnknownFlags(string $s, int $flags): void {
	if (preg_match('/(foo)(bar)(baz)/xyz', $s, $matches, $flags)) {
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

// https://github.com/hoaproject/Regex/issues/31
function hoaBug31(string $s): void {
	if (preg_match('/([\w-])/', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
	assertType('array{}|array{string, string}', $matches);

	if (preg_match('/\w-(\d+)-(\w)/', $s, $matches)) {
		assertType('array{string, string, string}', $matches);
	}
	assertType('array{}|array{string, string, string}', $matches);
}

// https://github.com/phpstan/phpstan/issues/10855#issuecomment-2044323638
function testHoaUnsupportedRegexSyntax(string $s): void {
	if (preg_match('#\QPHPDoc type array<string> of property App\Log::$fillable is not covariant with PHPDoc type array<int, string> of overridden property Illuminate\Database\E\\\\\QEloquent\Model::$fillable.\E#', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array{}|array{string}', $matches);
}

function testPregMatchSimpleCondition(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}


function testPregMatchIdenticalToOne(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) === 1) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneFalseyContext(string $value): void {
	if (!(preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) !== 1)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneInverted(string $value): void {
	if (1 === preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneFalseyContextInverted(string $value): void {
	if (!(1 !== preg_match('/%env\((.*)\:.*\)%/U', $value, $matches))) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOne(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) == 1) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneFalseyContext(string $value): void {
	if (!(preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) != 1)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneInverted(string $value): void {
	if (1 == preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneFalseyContextInverted(string $value): void {
	if (!(1 != preg_match('/%env\((.*)\:.*\)%/U', $value, $matches))) {
		assertType('array{string, string}', $matches);
	}
}
